<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email_opt_in' => ['sometimes', 'boolean'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('password')) {
                return;
            }

            $currentPassword = $this->input('current_password');
            $user = $this->user();

            if (! is_string($currentPassword) || $user === null || ! Hash::check($currentPassword, $user->password)) {
                $validator->errors()->add('current_password', __('messages.current_password_incorrect'));
            }
        });
    }
}
