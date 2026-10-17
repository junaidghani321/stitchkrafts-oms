<?php

namespace App\Models;

use App\Enums\Country;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The profile fields a customer account carries beyond its login.
 *
 * `user_id` is unique, so this is the hasOne side of `User::detail()`.
 */
#[Fillable(['user_id', 'username', 'country', 'company_name', 'contact_number'])]
class UserDetail extends Model
{
    protected function casts(): array
    {
        return ['country' => Country::class];
    }

    #[Scope]
    protected function inCountry(Builder $query, Country|string $country): Builder
    {
        return $query->where('country', $country instanceof Country ? $country->value : $country);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
