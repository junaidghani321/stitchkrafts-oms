<?php

namespace App\Http\Requests\Tasks;

use App\Http\Requests\Orders\Concerns\SharedOrderRules;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
{
    use SharedOrderRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $config = config('orders.uploads');

        return [
            'note' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:'.$config['max_files']],
            'files.*' => ['file', 'max:'.$config['max_size'], 'extensions:'.implode(',', $config['extensions'])],
        ];
    }

    public function messages(): array
    {
        return [
            'files.*.extensions' => 'That file type is not supported. Accepted: :values',
        ];
    }
}
