<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceOrderFileRequest extends FormRequest
{
    /** Authorization is OrderFilePolicy@replace — see the controller. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $config = config('orders.uploads');

        /*
         | One file, and `required`: a replacement with nothing to replace it
         | with would otherwise supersede the employee's work and leave the
         | order with no deliverable at all.
         |
         | `extensions:` rather than `mimes:` for the reason given in
         | SharedOrderRules — embroidery formats resolve to
         | application/octet-stream and mimes: rejects them.
         */
        return [
            'file' => [
                'required',
                'file',
                'max:'.$config['max_size'],
                'extensions:'.implode(',', $config['extensions']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Choose the file that should replace it.',
            'file.extensions' => 'That file type is not supported. Accepted: :values',
        ];
    }
}
