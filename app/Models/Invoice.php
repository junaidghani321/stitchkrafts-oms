<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Observers\InvoiceObserver;
use App\Policies\InvoicePolicy;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Totals and payment_status are excluded from fillable: they are derived from
 * the line items and from recorded payments, never posted.
 */
#[Fillable(['customer_id', 'created_by_id', 'issued_date', 'due_date', 'currency', 'notes'])]
#[ObservedBy(InvoiceObserver::class)]
#[UsePolicy(InvoicePolicy::class)]
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'payment_status' => PaymentStatus::class,
            'subtotal_cents' => 'integer',
            'total_cents' => 'integer',
            'paid_cents' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        // withTrashed: who was billed is a historical fact.
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, InvoiceItem::class, 'invoice_id', 'id', 'id', 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Only the money that actually landed. */
    public function successfulPayments(): HasMany
    {
        return $this->payments()->where('status', Payment::STATUS_SUCCEEDED);
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

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('payment_status', [
            PaymentStatus::Unpaid->value,
            PaymentStatus::Partial->value,
        ]);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Paid->value);
    }

    public function scopeForIndex(Builder $query): Builder
    {
        return $query
            ->latest('issued_date')
            ->latest('id')
            ->limit(config('orders.index.max_rows', 1000));
    }

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    */

    /** What is still owed, in cents. Never negative. */
    protected function outstandingCents(): Attribute
    {
        return Attribute::get(fn () => max(0, $this->total_cents - $this->paid_cents));
    }

    /** Decimal amounts for the views, which format with number_format(). */
    protected function total(): Attribute
    {
        return Attribute::get(fn () => Money::toDecimal($this->total_cents));
    }

    protected function paid(): Attribute
    {
        return Attribute::get(fn () => Money::toDecimal($this->paid_cents));
    }

    protected function outstanding(): Attribute
    {
        return Attribute::get(fn () => Money::toDecimal($this->outstanding_cents));
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isPayable(): bool
    {
        return ! $this->isPaid() && $this->outstanding_cents > 0;
    }
}
