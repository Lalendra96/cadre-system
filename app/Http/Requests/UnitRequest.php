<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isPlanningOfficer();
    }

    public function rules(): array
    {
        $id = $this->route('unit');

        return [
            'code'      => ['required', 'string', 'max:30', Rule::unique('units', 'code')->ignore($id)],
            'name'      => ['required', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ];
    }
}
