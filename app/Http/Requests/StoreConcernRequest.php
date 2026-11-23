<?php

namespace App\Http\Requests;

use App\Enums\ConcernPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreConcernRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isStudent() === true && $user->is_active === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'subject' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(ConcernPriority::class)],
            'recipient_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereExists(fn ($roleQuery) => $roleQuery
                        ->select(DB::raw(1))
                        ->from('roles')
                        ->whereColumn('roles.id', 'users.role_id')
                        ->where('roles.name', 'teacher'))),
            ],
            'is_anonymous' => ['sometimes', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt',
                'max:5120',
            ],
        ];
    }
}
