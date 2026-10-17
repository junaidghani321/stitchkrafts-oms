<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * What a user may change about themselves.
 *
 * Deliberately narrower than Users\UpdateUserRequest: `type`, `status`,
 * `designation`, `department`, `shift` and `notes` are all set BY the business
 * ABOUT the user, so they stay on the admin screen. Letting them through here
 * would hand every employee their own promotion.
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            // Only customers are shown this field; see the view.
            'company' => ['nullable', 'string', 'max:150'],
        ];

        // Changing the email is step one of an account takeover: point it at
        // an address you control, then use "forgot password" to receive the
        // reset there. Proving knowledge of the current password means a
        // hijacked session alone is not enough. Only asked when the address
        // actually changes, so ordinary edits stay one click.
        if ($this->emailIsChanging()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }

    /** Case- and whitespace-insensitive, so re-typing the same address does not prompt. */
    private function emailIsChanging(): bool
    {
        $submitted = Str::lower(trim((string) $this->input('email')));

        return $submitted !== Str::lower((string) $this->user()->email);
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'That email address is already registered.',
            'current_password.required' => 'Enter your current password to change your email address.',
            'current_password.current_password' => 'That is not your current password.',
        ];
    }
}
