<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\MessageKind;
use App\Enums\UserType;
use App\Policies\UserPolicy;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * `type` is deliberately NOT in $fillable. It is a privilege column: setting it
 * is an explicit, audited act in the admin user controller, never something a
 * mass-assigned payload can reach.
 */
#[Fillable(['name', 'email', 'password', 'status', 'designation', 'department', 'shift', 'phone', 'company', 'notes'])]
#[Hidden(['password', 'remember_token'])]
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * The user's role.
     *
     * The column is a tinyInteger; this reads as a lowercase slug
     * ('user' | 'admin' | 'manager' | 'employee') and writes as an int.
     *
     * The string return is a hard contract — the UserAccess middleware,
     * LoginController and the sidebar's match($role) all compare against it.
     * Do not cast this attribute to UserType; use $user->type_enum instead.
     *
     * The tryFrom(...) ?? fallback matters: a constrained eager load such as
     * with('customer:id,name') omits the column, so $value arrives as null.
     */
    protected function type(): Attribute
    {
        return new Attribute(
            get: fn ($value) => (UserType::tryFrom((int) $value) ?? UserType::Customer)->slug(),
            set: fn (UserType|string|int $value) => UserType::coerce($value)->value,
        );
    }

    /**
     * The same role as an enum, for new code. Deliberately a different
     * attribute name so the string contract above stays intact.
     */
    protected function typeEnum(): Attribute
    {
        return Attribute::get(
            fn () => UserType::tryFrom((int) $this->getRawOriginal('type')) ?? UserType::Customer,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Never write where('type', 'employee') by hand — `type` is an int column,
    | so MySQL coerces the string to 0 and silently returns every customer.
    | Always go through these.
    |
    */

    public function scopeOfType(Builder $query, UserType|string|int $type): Builder
    {
        return $query->where('type', UserType::coerce($type)->value);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->ofType(UserType::Customer);
    }

    public function scopeEmployees(Builder $query): Builder
    {
        return $query->ofType(UserType::Employee);
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereIn('type', [
            UserType::Admin->value,
            UserType::Manager->value,
            UserType::Employee->value,
        ]);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /*
    |--------------------------------------------------------------------------
    | Role helpers
    |--------------------------------------------------------------------------
    */

    public function isCustomer(): bool
    {
        return $this->type_enum === UserType::Customer;
    }

    public function isAdmin(): bool
    {
        return $this->type_enum === UserType::Admin;
    }

    public function isManager(): bool
    {
        return $this->type_enum === UserType::Manager;
    }

    public function isEmployee(): bool
    {
        return $this->type_enum === UserType::Employee;
    }

    /** Admin and manager share every order-module permission except pricing. */
    public function isStaff(): bool
    {
        return $this->type_enum->isStaff();
    }

    public function isAdminOrManager(): bool
    {
        return $this->isAdmin() || $this->isManager();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /*
    |--------------------------------------------------------------------------
    | Email preferences
    |--------------------------------------------------------------------------
    */

    /** The switches this user's role offers, with their saved or default value. */
    public function emailPreferences(): array
    {
        $defined = config('notification_preferences.'.$this->type, []);
        $saved = $this->notification_preferences ?? [];

        $out = [];

        foreach ($defined as $key => $meta) {
            $out[$key] = $meta + [
                'key' => $key,
                // A key the user has never saved falls back to the default.
                'on' => array_key_exists($key, $saved) ? (bool) $saved[$key] : (bool) $meta['default'],
            ];
        }

        return $out;
    }

    /**
     * Should this user be emailed about `$key`?
     *
     * Unknown keys and keys their role does not offer return false, so a
     * notification can never mail somebody a preference they cannot see or
     * turn off.
     */
    public function prefers(?string $key): bool
    {
        if ($key === null) {
            return false;
        }

        $meta = config("notification_preferences.{$this->type}.{$key}");

        if ($meta === null) {
            return false;
        }

        $saved = $this->notification_preferences ?? [];

        return array_key_exists($key, $saved) ? (bool) $saved[$key] : (bool) $meta['default'];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** The customer profile — username, country, company, contact number. */
    public function detail(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }

    /** Orders this user placed as the customer. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /** Orders this user created on someone else's behalf. */
    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by_id');
    }

    /** Tasks assigned to this user as the employee. */
    public function tasks(): HasMany
    {
        return $this->hasMany(OrderTask::class, 'employee_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(OrderTask::class, 'assigned_by_id');
    }

    /** Revisions this user raised — they are the message author. */
    public function requestedRevisions(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'user_id')
            ->where('kind', MessageKind::Revision->value);
    }
}
