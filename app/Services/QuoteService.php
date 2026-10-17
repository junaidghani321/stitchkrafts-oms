<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderFile;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(
        private readonly OrderFileService $files,
        private readonly OrderNotifier $notifier,
    ) {}

    /**
     * Put a price on an enquiry: pending -> quoted.
     *
     * Records who prepared it, which is what the employee "Quotes I Prepared"
     * screen lists.
     */
    public function price(Quote $quote, float $price, ?string $validUntil, User $actor): Quote
    {
        return DB::transaction(function () use ($quote, $price, $validUntil, $actor) {
            $quote->forceFill([
                'price' => $price,
                'status' => QuoteStatus::Quoted,
                'priced_by_id' => $actor->id,
                'priced_at' => now(),
                'valid_until' => $validUntil,
                // Staff pricing on their own counts as preparing it; an
                // existing preparer is not overwritten.
                'prepared_by_id' => $quote->prepared_by_id ?? $actor->id,
            ])->save();

            $this->notifier->quotePriced($quote->loadMissing('customer'));

            return $quote;
        });
    }

    /**
     * The customer accepts: the quote becomes a real order.
     *
     * The spec is COPIED rather than moved, so the quote stays on file as the
     * record of what was agreed and at what price. converted_order_id links
     * the two.
     */
    public function approve(Quote $quote, User $actor): Order
    {
        return DB::transaction(function () use ($quote, $actor) {
            $order = new Order($quote->specAttributes());

            $order->customer_id = $quote->customer_id;
            $order->created_by_id = $quote->customer_id === $actor->id ? null : $actor->id;
            $order->save();

            // due_at needs created_at, so it is derived once the row exists.
            $order->due_at = $order->computeDueAt();

            // The agreed price carries over — the customer already accepted it.
            $order->price = $quote->price;
            $order->priced_by_id = $quote->priced_by_id;
            $order->priced_at = $quote->priced_at;
            $order->saveQuietly();

            // The artwork goes with it: copied, so the quote keeps its own set
            // and deleting one can never strip the other's files.
            $this->copyArtwork($quote, $order, $actor);

            $quote->forceFill([
                'status' => QuoteStatus::Approved,
                'approved_at' => now(),
                'converted_order_id' => $order->id,
            ])->save();

            OrderActivity::create([
                'order_id' => $order->id,
                'user_id' => $actor->id,
                'event' => OrderActivity::ORDER_CREATED,
                'description' => "Created from accepted quote {$quote->quote_number}",
                'properties' => ['quote_number' => $quote->quote_number, 'price' => $quote->price],
            ]);

            $this->notifier->quoteApproved($quote, $order);

            return $order;
        });
    }

    /** The customer declines, or staff withdraw it. */
    public function reject(Quote $quote, ?string $reason, User $actor): Quote
    {
        return DB::transaction(function () use ($quote, $reason) {
            $quote->forceFill([
                'status' => QuoteStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->notifier->quoteRejected($quote->loadMissing('customer'));

            return $quote;
        });
    }

    /**
     * Sweep quotes past their validity date.
     *
     * Called on read rather than by a scheduled job — there is no guaranteed
     * worker, and an "expired" quote that still shows as live is worse than a
     * cheap check per listing.
     */
    public function expireStale(): int
    {
        return Quote::query()
            ->open()
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => QuoteStatus::Expired->value]);
    }

    private function copyArtwork(Quote $quote, Order $order, User $actor): void
    {
        foreach ($quote->artwork as $file) {
            $this->files->copy($file, $order, OrderFile::CATEGORY_ARTWORK, $actor);
        }
    }
}
