<?php

namespace App\Http\Requests\Quotes;

use Illuminate\Foundation\Http\FormRequest;

class PriceQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            // A price should not stand forever, but dating it is optional.
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Enter the price you are quoting.',
            'valid_until.after_or_equal' => 'A quote cannot expire in the past.',
        ];
    }
}
