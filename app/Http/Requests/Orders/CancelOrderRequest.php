<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    /** Authorization is the OrderPolicy's job — see the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Optional, but capped to what the column holds so a long paste
            // fails validation rather than being silently truncated.
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'Please keep the reason under 500 characters.',
        ];
    }
}
