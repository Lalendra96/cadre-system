<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() || $this->user()?->isPlanningOfficer();
    }

    public function rules(): array
    {
        $id = $this->route('position');

        return [
            'code'      => ['required', 'string', 'max:30', Rule::unique('positions', 'code')->ignore($id)],
            'title'     => ['required', 'string', 'max:150'],
            'is_active' => ['boolean'],
        ];
    }
}
