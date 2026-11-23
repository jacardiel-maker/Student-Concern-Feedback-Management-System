<?php

namespace App\Http\Requests;

use App\Enums\ConcernStatus;
use App\Models\Concern;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateConcernStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $concern = $this->route('concern');
        $user = $this->user();

        return $concern instanceof Concern
            && $user !== null
            && $concern->canBeViewedBy($user)
            && ($user->isTeacher() || $user->isAdmin());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ConcernStatus::class)],
            'note' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('status')) {
                    return;
                }

                $concern = $this->route('concern');

                if (! $concern instanceof Concern) {
                    return;
                }

                $target = ConcernStatus::from($this->string('status')->toString());

                if (! $concern->status->canTransitionTo($target)) {
                    $validator->errors()->add('status', __('messages.invalid_transition', [
                        'from' => $concern->status->label(),
                        'to' => $target->label(),
                    ]));
                }
            },
        ];
    }
}
