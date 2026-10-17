<?php

namespace App\Http\Requests\Orders\User;

use App\Enums\ExpectedDelivery;
use App\Http\Requests\Orders\Concerns\SharedOrderRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Patch is the strict one — nearly every field on the form is required, and
 * the turnaround field is `expected_delivery` (days), not `time_frame` (hours).
 * There is no `unit`: the labels hard-code inches.
 */
class StorePatchOrderRequest extends FormRequest
{
    use SharedOrderRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->sharedRules() + $this->artworkRules() + [
            'height' => ['required', 'numeric', 'min:0', 'max:999999'],
            'width' => ['required', 'numeric', 'min:0', 'max:999999'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'colors' => ['required', 'integer', 'min:1', 'max:9999'],
            'patch_type' => ['required', Rule::in(array_keys(config('orders.patch_types')))],
            'patch_background' => ['required', Rule::in(array_keys(config('orders.patch_backgrounds')))],
            'backing_type' => ['required', Rule::in(array_keys(config('orders.backing_types')))],
            'patch_border' => ['required', Rule::in(array_keys(config('orders.patch_borders')))],
            'expected_delivery' => ['required', Rule::enum(ExpectedDelivery::class)],
        ];
    }

    public function messages(): array
    {
        return $this->sharedMessages() + [
            'expected_delivery.required' => 'Please choose an expected delivery window.',
        ];
    }
}
