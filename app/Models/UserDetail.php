<?php

namespace App\Models;

use App\Enums\Country;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * The profile fields a customer account carries beyond its login.
 *
 * `user_id` is unique, so this is the hasOne side of `User::detail()`.
 */
#[Fillable(['user_id', 'username', 'country', 'company_name', 'contact_number'])]
class UserDetail extends Model
{
    /**
     * The longest username a customer may type: the column's 50 characters
     * less the two the country code takes once qualifyUsername() appends it.
     */
    public const USERNAME_MAX = 48;

    protected function casts(): array
    {
        return ['country' => Country::class];
    }

    /**
     * The stored form of a username: the letters the customer typed with their
     * country code appended, so `john` registering from the US is kept as
     * `johnus` and the same handle stays free in the UK and Australia. The
     * column's unique index is what enforces one per country.
     *
     * Lowercased, so the handle reads the same whatever case it was typed in.
     */
    public static function qualifyUsername(string $username, Country|string $country): string
    {
        $code = $country instanceof Country ? $country->value : $country;

        return Str::lower($username.$code);
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
