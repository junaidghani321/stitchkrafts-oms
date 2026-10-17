<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Extending is its own action with its own payload. The original markup reused
 * the assign modal here, which posted employee_id/task_type/order_id for what
 * is really just a duration change — and carried no task id at all.
 */
class ExtendTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'additional_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
