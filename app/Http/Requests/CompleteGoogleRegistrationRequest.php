<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteGoogleRegistrationRequest extends FormRequest
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
            'student_number' => ['required', 'string', 'max:20', Rule::unique('users', 'student_number')],
            'department' => ['nullable', 'string', 'max:120'],
        ];
    }
}
