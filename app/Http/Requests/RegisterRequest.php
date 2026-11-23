<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'student_number' => ['required', 'string', 'max:20', Rule::unique('users', 'student_number')],
            'department' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
