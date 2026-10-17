<?php

namespace App\Http\Requests\Tasks;

use App\Enums\MessageKind;
use App\Enums\Priority;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', Rule::exists('orders', 'id')],

            // Must be an ACTIVE EMPLOYEE. Without the type clause this would
            // happily accept a customer id; without the status clause it would
            // assign work to a deactivated account.
            'employee_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('type', UserType::Employee->value)
                    ->where('status', 'active'),
            ],

            // No task_type here on purpose. The type of work is read off the
            // order by TaskAssignmentService::taskTypeFor(), so a posted value
            // is neither needed nor trusted — validated() drops it.

            // min:1 is load-bearing — 0 would divide by zero in the views'
            // percent-of-allowed-time calculation.
            'duration_hours' => ['required', 'integer', 'min:1', 'max:720'],

            'priority' => ['nullable', Rule::enum(Priority::class)],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'notify' => ['nullable', 'boolean'],

            /*
             | Optional: the staff revision screen assigns through
             | RevisionService, so nothing posts this today. When something
             | does, its presence is what makes the task revision work.
             |
             | Scoped to kind = revision, which the old order_revisions
             | existence rule could not express: a comment id posted here would
             | otherwise create a revision task pointing at a comment.
             */
            'order_message_id' => ['nullable', Rule::exists('order_messages', 'id')
                ->where('kind', MessageKind::Revision->value)],
        ];
    }

    /**
     * An unchecked checkbox is not posted at all, so without this the "notify
     * the employee" switch could only ever be on — absence would fall through
     * to the service's default of true.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['notify' => $this->boolean('notify')]);
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Choose an active employee to assign this task to.',
            'duration_hours.min' => 'A task must allow at least one hour.',
        ];
    }
}
