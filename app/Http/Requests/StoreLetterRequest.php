<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSubjectOfficer() ?? false;
    }

    public function rules(): array
    {
        // Includes a currently-effective acting Subject Officer assignment,
        // not just permanent assignments — see User::effectiveSubjectCodeIds().
        $assignedIds = $this->user()->effectiveSubjectCodeIds()->all();

        // Only specific, individually-selected people may be picked — never
        // "everyone in the category". Build the actual pool of valid IDs so
        // the rule can't be satisfied by guessing a user_id outside it.
        $eligibleIds = User::active()
            ->havingRole(User::ROLE_ADMIN_GROUP)
            ->with('category')
            ->get()
            ->filter(fn ($u) => $u->isLetterRecipientCategory())
            ->pluck('id')
            ->all();

        return [
            'title'                => ['required', 'string', 'max:200'],
            'description'          => ['nullable', 'string', 'max:2000'],
            'subject_code_id'      => ['nullable', 'integer', Rule::in($assignedIds)],
            'recipient_user_ids'   => ['required', 'array', 'min:1'],
            'recipient_user_ids.*'  => ['integer', Rule::in($eligibleIds)],
            'attachments'           => ['required', 'array', 'min:1'],
            'attachments.*'         => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'], // 10MB each
        ];
    }

    public function messages(): array
    {
        return [
            'subject_code_id.in'      => 'You may only attach a subject code that is assigned to your account.',
            'recipient_user_ids.required' => 'Select at least one recipient.',
            'recipient_user_ids.*.in' => 'One of the selected recipients is not eligible to receive letters.',
            'attachments.required'    => 'Add at least one file.',
            'attachments.*.max'       => 'Each file may not be larger than 10MB.',
        ];
    }
}
