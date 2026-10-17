<?php

namespace App\Enums;

/**
 * The `users.type` column is a tinyInteger, so this enum is int-backed.
 *
 * Note the vocabulary split: the case is `Customer` but the slug is `user`,
 * because `user` is the string the routes, middleware and sidebar already use.
 */
enum UserType: int
{
    case Customer = 0;
    case Admin = 1;
    case Manager = 2;
    case Employee = 3;

    /**
     * The lowercase string every existing call site expects
     * (UserAccess middleware, LoginController, the layout's match($role)).
     */
    public function slug(): string
    {
        return match ($this) {
            self::Customer => 'user',
            self::Admin => 'admin',
            self::Manager => 'manager',
            self::Employee => 'employee',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }

    public static function fromSlug(string $slug): self
    {
        return match ($slug) {
            'user' => self::Customer,
            'admin' => self::Admin,
            'manager' => self::Manager,
            'employee' => self::Employee,
            default => throw new \ValueError("Unknown user type slug [{$slug}]."),
        };
    }

    /**
     * Accept whatever a caller happens to hold — enum, slug or raw int.
     * This is what lets the `type` mutator take `'employee'`, `3` or the enum.
     */
    public static function coerce(self|string|int $value): self
    {
        return match (true) {
            $value instanceof self => $value,
            is_int($value) => self::from($value),
            default => self::fromSlug($value),
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /** value => label, for the users CRUD role select. */
    public static function options(): array
    {
        return [
            self::Customer->slug() => self::Customer->label(),
            self::Employee->slug() => self::Employee->label(),
            self::Manager->slug() => self::Manager->label(),
            self::Admin->slug() => self::Admin->label(),
        ];
    }
}
