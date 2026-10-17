<?php

namespace App\Http\Requests\Revisions;

use Illuminate\Foundation\Http\FormRequest;

class RejectRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
