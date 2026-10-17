<?php

namespace App\Http\Requests\Revisions;

use App\Enums\Priority;
use App\Enums\RevisionReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * order_id is the real primary key. The original markup posted either a
 * display string ("ADDO-442") or, on the revisions page, a revision number
 * ("REV-0031") into this field.
 *
 * Whether the actor may raise a revision against that order is a policy
 * question, checked in the controller.
 */
class StoreRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $config = config('orders.uploads');

        return [
            'order_id' => ['required', Rule::exists('orders', 'id')],
            'reason' => ['required', Rule::enum(RevisionReason::class)],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'due_date' => ['nullable', 'date'],
            'comments' => ['required', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:'.$config['max_files']],
            'files.*' => ['file', 'max:'.$config['max_size'], 'extensions:'.implode(',', $config['extensions'])],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Please choose the order this revision is for.',
            'comments.required' => 'Please describe what needs to change.',
            'files.*.extensions' => 'That file type is not supported. Accepted: :values',
        ];
    }
}
