<?php

namespace App\Http\Requests\Orders;

use App\Enums\ExpectedDelivery;
use App\Enums\OrderType;
use App\Enums\TimeFrame;
use App\Http\Requests\Orders\Concerns\SharedOrderRules;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Editing is a single route per role, so this is the one place a match() on
 * order type is justified — the type comes from the bound model, not the
 * payload, and is never editable.
 *
 * Artwork is optional here: an edit that attaches nothing keeps the files
 * already on the order.
 */
class UpdateOrderRequest extends FormRequest
{
    use SharedOrderRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->sharedRules()
            + $this->artworkRules(required: false)
            + $this->rulesForType($this->order()->type);
    }

    private function order(): Order
    {
        return $this->route('order');
    }

    private function rulesForType(OrderType $type): array
    {
        return match ($type) {
            OrderType::Digitizing => [
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
            ],
            OrderType::Vector => [
                'required_format' => ['nullable', Rule::in(array_keys(config('orders.formats.vector')))],
                'color_type' => ['nullable', Rule::in(array_keys(config('orders.color_types')))],
                'printing_process' => ['nullable', Rule::in(array_keys(config('orders.printing_processes')))],
                'time_frame' => ['required', Rule::enum(TimeFrame::class)],
            ],
            OrderType::Patch => [
                'height' => ['required', 'numeric', 'min:0', 'max:999999'],
                'width' => ['required', 'numeric', 'min:0', 'max:999999'],
                'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
                'colors' => ['required', 'integer', 'min:1', 'max:9999'],
                'patch_type' => ['required', Rule::in(array_keys(config('orders.patch_types')))],
                'patch_background' => ['required', Rule::in(array_keys(config('orders.patch_backgrounds')))],
                'backing_type' => ['required', Rule::in(array_keys(config('orders.backing_types')))],
                'patch_border' => ['required', Rule::in(array_keys(config('orders.patch_borders')))],
                'expected_delivery' => ['required', Rule::enum(ExpectedDelivery::class)],
            ],
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->order()->type !== OrderType::Digitizing) {
            return;
        }

        $this->merge([
            'puff_3d' => $this->toBoolean($this->input('puff_3d')),
            'applique' => $this->toBoolean($this->input('applique')),
            'trims' => $this->toBoolean($this->input('trims')),
            'patch_cost' => $this->toBoolean($this->input('patch_cost')),
        ]);
    }

    public function messages(): array
    {
        return $this->sharedMessages();
    }
}
