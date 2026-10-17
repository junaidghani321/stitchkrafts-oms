<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * `order_number` is left out on purpose: OrderObserver stamps it from the
     * row's own id once the insert has happened.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => OrderType::Digitizing,
            'customer_id' => User::factory()->customer(),
            'design_name' => fake()->words(2, true),
            'status' => OrderStatus::Pending,
            'due_at' => now()->addDays(3),
        ];
    }

    /** Work handed in, waiting on an admin or manager to sign it off. */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PendingApproval,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function ofType(OrderType $type): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type]);
    }
}
