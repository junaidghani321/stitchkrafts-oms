<?php

namespace App\Models;

use App\Policies\OrderFilePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Deliberately has no url() accessor. These files live on a private disk and
 * the only route to them is route('files.download', $file), which authorizes
 * through OrderFilePolicy first.
 */
#[Fillable(['category', 'disk', 'path', 'original_name', 'mime_type', 'size', 'uploaded_by_id'])]
#[UsePolicy(OrderFilePolicy::class)]
class OrderFile extends Model
{
    public const CATEGORY_ARTWORK = 'artwork';

    public const CATEGORY_DELIVERABLE = 'deliverable';

    public const CATEGORY_REVISION_REQUEST = 'revision_request';

    public const CATEGORY_REVISION_DELIVERABLE = 'revision_deliverable';

    public const CATEGORY_MESSAGE = 'message';

    /** What a customer is allowed to see of an order's output. */
    public const CUSTOMER_GATED = [
        self::CATEGORY_DELIVERABLE,
        self::CATEGORY_REVISION_DELIVERABLE,
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Delete the blob with the row. Orders are soft-deleted, so files
        // survive a soft delete and only vanish on forceDelete.
        static::deleting(function (OrderFile $file) {
            Storage::disk($file->disk)->delete($file->path);
        });
    }

    /**
     * Signed off by an approver, and therefore visible to the customer.
     * Anything that is not gated at all (artwork, message attachments) counts
     * as approved — the gate is only about work handed in for sign-off.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNotIn('category', self::CUSTOMER_GATED)
                ->orWhereNotNull('approved_at');
        });
    }

    /** Handed in, not yet signed off. Staff only. */
    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->whereIn('category', self::CUSTOMER_GATED)->whereNull('approved_at');
    }

    public function isAwaitingApproval(): bool
    {
        return in_array($this->category, self::CUSTOMER_GATED, true) && $this->approved_at === null;
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id')->withTrashed();
    }

    /** The Order this file ultimately belongs to, whatever it is attached to. */
    public function order(): ?Order
    {
        $attachable = $this->attachable;

        return match (true) {
            $attachable instanceof Order => $attachable,
            $attachable instanceof OrderMessage => $attachable->order,
            default => null,
        };
    }

    protected function humanSize(): Attribute
    {
        return Attribute::get(function () {
            $bytes = (int) $this->size;

            return match (true) {
                $bytes >= 1048576 => round($bytes / 1048576, 1).' MB',
                $bytes >= 1024 => round($bytes / 1024).' KB',
                default => $bytes.' B',
            };
        });
    }

    protected function extension(): Attribute
    {
        return Attribute::get(fn () => strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)));
    }
}
