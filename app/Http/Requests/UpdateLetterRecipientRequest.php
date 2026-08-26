<?php

namespace App\Http\Requests;

use App\Models\LetterRecipient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLetterRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Further checked in the controller: the row must actually belong to this user.
        return $this->user()?->isLetterRecipientCategory() ?? false;
    }

    public function rules(): array
    {
        return [
            'status'  => ['required', Rule::in(LetterRecipient::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
