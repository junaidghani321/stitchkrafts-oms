<?php

namespace App\Http\Requests\Users;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],

            // Deliberately NOT ignoring soft-deleted rows: the database unique
            // index covers them too, so allowing it here would just turn a
            // clear validation message into a SQL error.
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')],

            'type' => ['required', Rule::in(array_keys(UserType::options()))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:5000'],

            // Employee profile fields — what the Employees screens display.
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'shift' => ['nullable', 'string', 'max:60'],

            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'That email address is already registered.',
            'type.required' => 'Choose what kind of account this is.',
        ];
    }
}
