<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Nullable so a price can be cleared back to "not yet priced".
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }
}
