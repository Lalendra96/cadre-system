<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Approved Carder write request.
 *
 * Authorization: Super Admin OR Admin Group Director.
 * Planning Officers may VIEW approved carder data but cannot modify it —
 * these are Ministry-issued figures approved at Director level.
 */
class StoreApprovedCarderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageApprovedCarder();
    }

    public function rules(): array
    {
        $id = $this->route('approved_carder')?->id;

        return [
            'position_id' => [
                'required', 'integer', 'exists:positions,id',
                Rule::unique('approved_carders', 'position_id')
                    ->where('year', $this->input('year'))
                    ->ignore($id),
            ],
            'year'                  => ['required', 'integer', 'min:2000', 'max:2100'],
            'approved_amount'       => ['required', 'integer', 'min:0', 'max:99999'],
            'ministry_reference_no' => ['nullable', 'string', 'max:100'],
            'approved_date'         => ['nullable', 'date'],
            'remarks'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'position_id.unique' => 'An approved carder record already exists for this position and year. Edit the existing one instead.',
            'approved_amount.max' => 'Approved amount seems unusually high. Please verify the figure.',
        ];
    }

    protected function failedAuthorization(): never
    {
        throw new \Illuminate\Auth\Access\AuthorizationException(
            'Access to approved carder records is granted to: Super Admin, Planning Officer, '
            . 'Director, Deputy Director General, Deputy Director, and Medical Officer Planning.'
        );
    }
}
