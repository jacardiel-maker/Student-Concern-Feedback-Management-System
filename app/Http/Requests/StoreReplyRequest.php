<?php

namespace App\Http\Requests;

use App\Models\Concern;
use Illuminate\Foundation\Http\FormRequest;

class StoreReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $concern = $this->route('concern');

        return $concern instanceof Concern
            && $this->user()?->can('participate', $concern) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'is_internal_note' => ['sometimes', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function prepareForValidation(): void
    {
        if (! $this->user()?->isTeacher() && ! $this->user()?->isAdmin()) {
            $this->merge(['is_internal_note' => false]);
        }
    }
}
