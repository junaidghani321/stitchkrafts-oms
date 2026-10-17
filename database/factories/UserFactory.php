<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    |
    | `type` is not fillable on the model — it is a privilege column — but a
    | factory runs unguarded, so the states below set it directly.
    |
    | The backed value, not the enum case: assigning an enum to an Attribute
    | with object caching makes the in-memory read hand back that same enum,
    | while User::type() is contracted to read as a lowercase slug. The
    | difference only shows on the instance that did the assigning — which is
    | precisely the instance a test passes to actingAs().
    |
    */

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => ['type' => UserType::Customer->value]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['type' => UserType::Admin->value]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => ['type' => UserType::Manager->value]);
    }

    public function employee(): static
    {
        return $this->state(fn (array $attributes) => ['type' => UserType::Employee->value]);
    }
}
