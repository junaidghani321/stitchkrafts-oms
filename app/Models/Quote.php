<?php

namespace App\Models;

use App\Enums\ExpectedDelivery;
use App\Enums\OrderType;
use App\Enums\QuoteStatus;
use App\Enums\TimeFrame;
use App\Observers\QuoteObserver;
use App\Policies\QuotePolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Same fillable spec list as Order — the quote forms are field-for-field
 * identical to the order forms. status, price and the decision timestamps are
 * excluded: those are set by QuoteService, never by a posted payload.
 */
#[Fillable([
    'type', 'customer_id', 'created_by_id', 'design_name', 'instructions',
    'time_frame', 'required_format',
    'height', 'width', 'colors',
    'unit', 'fabric', 'placement', 'puff_3d', 'applique', 'trims', 'patch_cost',
    'color_type', 'printing_process',
    'expected_delivery', 'quantity', 'patch_type', 'patch_background', 'backing_type', 'patch_border',
])]
#[ObservedBy(QuoteObserver::class)]
#[UsePolicy(QuotePolicy::class)]
class Quote extends Model
{
    use HasFactory, SoftDeletes;

    /** The spec fields copied verbatim into an Order on approval. */
    public const SPEC_FIELDS = [
        'type', 'design_name', 'instructions',
        'time_frame', 'required_format',
        'height', 'width', 'colors',
        'unit', 'fabric', 'placement', 'puff_3d', 'applique', 'trims', 'patch_cost',
        'color_type', 'printing_process',
        'expected_delivery', 'quantity', 'patch_type', 'patch_background', 'backing_type', 'patch_border',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => QuoteStatus::class,
            'time_frame' => TimeFrame::class,
            'expected_delivery' => ExpectedDelivery::class,
            'price' => 'decimal:2',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'colors' => 'integer',
            'quantity' => 'integer',
            'puff_3d' => 'boolean',
            'applique' => 'boolean',
            'trims' => 'boolean',
            'patch_cost' => 'boolean',
            'valid_until' => 'date',
            'priced_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id')->withTrashed();
    }

    public function pricedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'priced_by_id')->withTrashed();
    }

    /**
     * Who worked the numbers. Quoting is an admin/manager job, so this is
     * always one of them — the column is kept so the credit is on record and
     * so employee involvement can be reintroduced without a migration.
     */
    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id')->withTrashed();
    }

    /** The order this quote became, once accepted. */
    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    /** Reuses the same polymorphic file table as orders. */
    public function files(): MorphMany
    {
        return $this->morphMany(OrderFile::class, 'attachable');
    }

    public function artwork(): MorphMany
    {
        return $this->files()->where('category', OrderFile::CATEGORY_ARTWORK);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForCustomer(Builder $query, User|int $customer): Builder
    {
        return $query->where('customer_id', $customer instanceof User ? $customer->id : $customer);
    }

    /** Quotes a given staff member priced. */
    public function scopePreparedBy(Builder $query, User|int $staff): Builder
    {
        return $query->where('prepared_by_id', $staff instanceof User ? $staff->id : $staff);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [QuoteStatus::Pending->value, QuoteStatus::Quoted->value]);
    }

    /** Full collection, capped — oms-table.js pages client-side. */
    public function scopeForIndex(Builder $query): Builder
    {
        return $query
            ->latest('quotes.created_at')
            ->limit(config('orders.index.max_rows', 1000));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & helpers
    |--------------------------------------------------------------------------
    */

    /** Never null — the views call number_format() on it. */
    protected function displayPrice(): Attribute
    {
        return Attribute::get(fn () => (float) ($this->price ?? 0));
    }

    public function isPriced(): bool
    {
        return $this->price !== null;
    }

    public function isConverted(): bool
    {
        return $this->converted_order_id !== null;
    }

    /**
     * Past its validity date and still undecided.
     *
     * Strictly BEFORE today, not isPast(). `valid_until` is cast to a date, so
     * it lands on midnight; isPast() would call a quote dated today expired
     * from 00:01 onwards, costing the customer their last day. This matches
     * QuoteService::expireStale(), which sweeps on `whereDate < today` — the
     * two must agree or a quote can be undecidable yet never swept.
     */
    public function hasExpired(): bool
    {
        return $this->valid_until !== null
            && $this->valid_until->lt(today())
            && $this->status->isOpen();
    }

    /** The spec, ready to hand to Order::create(). */
    public function specAttributes(): array
    {
        return collect(self::SPEC_FIELDS)
            ->mapWithKeys(fn (string $field) => [$field => $this->{$field}])
            ->all();
    }
}
