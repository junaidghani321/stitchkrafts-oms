<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\OrderType;
use App\Models\OrderFile;
use App\Models\Quote;
use App\Services\OrderFileService;
use App\Services\OrderNotifier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

/**
 * The one place a quote is created, shared by the customer and staff
 * controllers — the mirror of StoresOrders.
 */
trait StoresQuotes
{
    protected function persistQuote(FormRequest $request, OrderType $type, ?int $customerId = null): Quote
    {
        // validated(), never all(): a customer_id posted to a customer form is
        // dropped here because that form has no rule for it.
        $data = $request->validated();
        $actor = $request->user();

        unset($data['artwork']);

        $quote = DB::transaction(function () use ($data, $type, $customerId, $actor, $request) {
            $quote = new Quote($data);

            $quote->type = $type;
            $quote->customer_id = $customerId ?? $actor->id;
            $quote->created_by_id = $quote->customer_id === $actor->id ? null : $actor->id;

            $quote->save();

            app(OrderFileService::class)->store(
                $request->file('artwork', []),
                $quote,
                OrderFile::CATEGORY_ARTWORK,
                $actor,
            );

            return $quote;
        });

        $quote->refresh()->loadMissing('customer');

        app(OrderNotifier::class)->quoteRequested($quote);

        return $quote;
    }

    /** Everything the quote detail page renders, in one pass. */
    protected function loadQuoteDetail(Quote $quote): Quote
    {
        return $quote->load([
            'customer:id,name,type,company',
            'pricedBy:id,name,type',
            'preparedBy:id,name,type',
            'convertedOrder:id,order_number,status',
            'artwork',
        ]);
    }
}
