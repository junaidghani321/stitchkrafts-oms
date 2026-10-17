<?php

namespace App\Http\Requests\Orders;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Whether the move is legal from the CURRENT status is checked in
            // the controller against OrderStatus::canTransitionTo().
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ];
    }
}
