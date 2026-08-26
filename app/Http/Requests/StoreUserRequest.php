<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:150'],
            'email'             => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
            'roles'             => ['required', 'array', 'min:1'],
            'roles.*'           => [Rule::in(User::ROLES)],
            'category_id'       => ['nullable', 'integer', 'exists:user_categories,id'],
            'subject_code_ids'  => [
                Rule::requiredIf(fn () => in_array(User::ROLE_SUBJECT_OFFICER, $this->input('roles', []))),
                'nullable', 'array',
            ],
            'subject_code_ids.*' => ['integer', 'exists:subject_codes,id'],
            'is_active'          => ['boolean'],
            'can_view_employees' => ['boolean'],
            'can_view_letters'   => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required'          => 'At least one role must be assigned.',
            'subject_code_ids.required_if' => 'At least one subject code must be assigned to a Subject Officer account.',
        ];
    }
}
