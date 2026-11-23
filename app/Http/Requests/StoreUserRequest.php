<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'student_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'student_number'),
                Rule::requiredIf(fn (): bool => $this->isStudentRole()),
            ],
            'department' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_active' => ['sometimes', 'boolean'],
            'email_opt_in' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['role_id', 'student_number'])) {
                    return;
                }

                $role = Role::find($this->integer('role_id'));

                if ($role?->name === 'student' && empty($this->string('student_number')->toString())) {
                    $validator->errors()->add('student_number', __('messages.student_number_required'));
                }
            },
        ];
    }

    private function isStudentRole(): bool
    {
        return Role::find($this->integer('role_id'))?->name === 'student';
    }
}
