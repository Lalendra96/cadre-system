<?php

declare(strict_types=1);

namespace App\Http\Requests\ServiceLetters;

use Illuminate\Foundation\Http\FormRequest;

class PreviewServiceLetterRequest extends FormRequest
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
            'template_id' => ['required', 'integer', 'exists:service_letter_templates,id'],
        ];
    }
}
