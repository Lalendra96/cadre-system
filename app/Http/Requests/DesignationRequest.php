<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isPlanningOfficer();
    }

    public function rules(): array
    {
        $id = $this->route('designation');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('designations', 'code')->ignore($id)],
            'title' => ['required', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }
}
