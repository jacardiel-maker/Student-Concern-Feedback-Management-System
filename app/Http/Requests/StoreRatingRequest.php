<?php

namespace App\Http\Requests;

use App\Enums\ConcernStatus;
use App\Models\Concern;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $concern = $this->route('concern');

        return $concern instanceof Concern
            && $this->user() !== null
            && $concern->user_id === $this->user()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $concern = $this->route('concern');

                if (! $concern instanceof Concern) {
                    return;
                }

                if ($concern->status !== ConcernStatus::Resolved || $concern->rating !== null) {
                    $validator->errors()->add('score', __('messages.rating_not_allowed'));
                }
            },
        ];
    }
}
