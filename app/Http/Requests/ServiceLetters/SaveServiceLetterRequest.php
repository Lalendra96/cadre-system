<?php

declare(strict_types=1);

namespace App\Http\Requests\ServiceLetters;

use Illuminate\Foundation\Http\FormRequest;

class SaveServiceLetterRequest extends FormRequest
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
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'template_id' => ['nullable', 'integer', 'exists:service_letter_templates,id'],
            'letterhead_id' => ['nullable', 'integer', 'exists:service_letter_letterheads,id'],
            'language' => ['required', 'in:en,si,ta'],
            'purpose' => ['nullable', 'string', 'max:80'],
            'recipient_name' => ['nullable', 'string', 'max:180'],
            'recipient_address' => ['nullable', 'string', 'max:300'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'copy_type' => ['required', 'in:original,copy,certified_copy,draft,confidential'],
            'subject' => ['required', 'string', 'max:200'],
            'rendered_body' => ['required', 'string', 'max:10000'],
        ];
    }
}
