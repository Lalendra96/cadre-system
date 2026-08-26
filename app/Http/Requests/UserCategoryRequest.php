<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('category');

        return [
            'name'                => ['required', 'string', 'max:100',
                                      Rule::unique('user_categories', 'name')->ignore($id)],
            'description'         => ['nullable', 'string', 'max:255'],
            'is_active'           => ['boolean'],
            'can_receive_letters' => ['boolean'],
            'sort_order'          => ['integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A category with this name already exists.',
        ];
    }
}
