<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class ApproveOrderRequest extends FormRequest
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
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.max' => 'Please keep the approval note under 500 characters.',
        ];
    }
}
