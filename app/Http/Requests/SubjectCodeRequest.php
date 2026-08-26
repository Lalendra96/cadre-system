<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('subject_code');

        return [
            'code'         => ['required', 'string', 'max:30', Rule::unique('subject_codes', 'code')->ignore($id)],
            'name'         => ['required', 'string', 'max:150'],
            'position_ids' => ['nullable', 'array'],
            'position_ids.*' => ['integer', 'exists:positions,id'],
            'is_active'    => ['boolean'],
        ];
    }
}
