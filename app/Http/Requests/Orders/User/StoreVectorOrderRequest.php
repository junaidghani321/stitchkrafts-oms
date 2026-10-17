<?php

namespace App\Http\Requests\Orders\User;

use App\Enums\TimeFrame;
use App\Http\Requests\Orders\Concerns\SharedOrderRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vector carries none of the digitizing or patch spec fields. They have no
 * rules here, so validated() drops them even if they are posted.
 *
 * Note required_format uses the vector list (ai/eps/pdf/cdr/svg/psd/all),
 * which is disjoint from the digitizing one.
 */
class StoreVectorOrderRequest extends FormRequest
{
    use SharedOrderRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->sharedRules() + $this->artworkRules() + [
            'required_format' => ['nullable', Rule::in(array_keys(config('orders.formats.vector')))],
            'color_type' => ['nullable', Rule::in(array_keys(config('orders.color_types')))],
            'printing_process' => ['nullable', Rule::in(array_keys(config('orders.printing_processes')))],
            'time_frame' => ['required', Rule::enum(TimeFrame::class)],
        ];
    }

    public function messages(): array
    {
        return $this->sharedMessages() + [
            'time_frame.required' => 'Please choose a turnaround time.',
        ];
    }
}
