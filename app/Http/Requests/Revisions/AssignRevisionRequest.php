<?php

namespace App\Http\Requests\Revisions;

use App\Enums\Priority;
use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('type', UserType::Employee->value)
                    ->where('status', 'active'),
            ],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'notify' => ['nullable', 'boolean'],
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
}
