<?php

namespace App\Http\Requests\Orders\User;

use App\Enums\TimeFrame;
use App\Http\Requests\Orders\Concerns\SharedOrderRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Deliberately has no customer_id rule. The controller takes the customer from
 * the session, and validated() drops anything not covered by a rule — so a
 * customer_id smuggled into the payload never reaches Order::create().
 */
class StoreDigitizingOrderRequest extends FormRequest
{
    use SharedOrderRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->sharedRules() + $this->artworkRules() + [
            'height' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'unit' => ['nullable', Rule::in(array_keys(config('orders.units')))],
            'colors' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'fabric' => ['nullable', 'string', 'max:100'],
            'placement' => ['nullable', Rule::in(array_keys(config('orders.placements')))],
            'required_format' => ['nullable', Rule::in(array_keys(config('orders.formats.digitizing')))],
            'time_frame' => ['required', Rule::enum(TimeFrame::class)],
            'puff_3d' => ['nullable', 'boolean'],
            'applique' => ['nullable', 'boolean'],
            'trims' => ['nullable', 'boolean'],
            'patch_cost' => ['nullable', 'boolean'],
        ];
    }

    /** The yes/no selects post strings; the columns are nullable booleans. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'puff_3d' => $this->toBoolean($this->input('puff_3d')),
            'applique' => $this->toBoolean($this->input('applique')),
            'trims' => $this->toBoolean($this->input('trims')),
            'patch_cost' => $this->toBoolean($this->input('patch_cost')),
        ]);
    }

    public function messages(): array
    {
        return $this->sharedMessages() + [
            'time_frame.required' => 'Please choose a turnaround time.',
        ];
    }
}
