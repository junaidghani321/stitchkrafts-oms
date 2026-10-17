<?php

namespace App\Http\Requests\Orders\Concerns;

use App\Enums\UserType;
use Illuminate\Validation\Rule;

/**
 * The one field the staff create forms add.
 */
trait StaffCustomerRule
{
    protected function customerRule(): array
    {
        return [
            'customer_id' => [
                'required',
                // The ->where(type, 0) clause is not optional: without it an
                // admin could attach an order to another admin or to an
                // employee, and every ownership check downstream would be
                // reasoning about the wrong person.
                Rule::exists('users', 'id')->where('type', UserType::Customer->value),
            ],
        ];
    }
}
