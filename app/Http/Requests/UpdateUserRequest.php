<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name'              => ['required', 'string', 'max:150'],
            'email'             => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password'          => ['nullable', 'string', 'min:8', 'confirmed'],
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
}
