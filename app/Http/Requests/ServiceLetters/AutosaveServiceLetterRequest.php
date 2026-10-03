<?php

declare(strict_types=1);

namespace App\Http\Requests\ServiceLetters;

use Illuminate\Foundation\Http\FormRequest;

class AutosaveServiceLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'rendered_body' => ['required', 'string', 'max:20000'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:180'],
            'recipient_address' => ['nullable', 'string', 'max:300'],
            'document_classification' => ['required', 'in:internal,confidential,restricted,public'],
            'contains_personal_data' => ['required', 'boolean'],
            'access_note' => ['nullable', 'string', 'max:300'],
            'editor_lock_version' => ['required', 'integer', 'min:1'],
        ];
    }
}
