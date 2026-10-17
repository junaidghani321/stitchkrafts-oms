<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id', 'recorded_by_id', 'method', 'status', 'amount_cents', 'currency',
    'reference', 'payload', 'paid_at',
])]
class Payment extends Model
{
    /** Every payment is entered by hand; there is no payment gateway. */
    public const METHOD_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id')->withTrashed();
    }

    protected function amount(): Attribute
    {
        return Attribute::get(fn () => Money::toDecimal($this->amount_cents));
    }

    public function label(): string
    {
        return 'Recorded manually';
    }
}
