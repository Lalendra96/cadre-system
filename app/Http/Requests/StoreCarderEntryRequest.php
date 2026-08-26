<?php

namespace App\Http\Requests;

use App\Models\ApprovedCarder;
use App\Models\CarderMonthlyEntry;
use App\Models\SubjectCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarderEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Planning Officers and Super Admin can also submit entries
        // (e.g. fixing missing data on behalf of an officer).
        $user = $this->user();
        return $user?->isSubjectOfficer()
            || $user?->isPlanningOfficer()
            || $user?->isSuperAdmin();
    }

    public function rules(): array
    {
        $user = $this->user();

        // Subject Officers can only submit for their own assigned codes —
        // including a currently-effective acting assignment (see
        // User::effectiveSubjectCodeIds() docblock).
        // Super Admin and Planning Officer can submit for any code.
        if ($user->isSuperAdmin() || $user->isPlanningOfficer()) {
            $assignedIds = SubjectCode::pluck('id')->all();
        } else {
            $assignedIds = $user->effectiveSubjectCodeIds()->all();
        }

        // Position must be one of those linked to the chosen subject code.
        $validPositionIds = [];
        if ($codeId = (int) $this->input('subject_code_id')) {
            $code = SubjectCode::find($codeId);
            if ($code && (in_array($codeId, $assignedIds, true) || $user->isSuperAdmin() || $user->isPlanningOfficer())) {
                $validPositionIds = $code->positions()->pluck('positions.id')->all();
            }
        }

        // For updates, ignore the current entry in the uniqueness check.
        $entryId = $this->route('carder_entry')?->id
            ?? $this->route('entry')?->id;

        return [
            'subject_code_id' => ['required', 'integer', Rule::in($assignedIds)],
            'position_id'     => ['required', 'integer', Rule::in($validPositionIds ?: [0])],
            'year'            => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'           => ['required', 'integer', 'min:1', 'max:12'],

            // Uniqueness: one entry per (subject_code, position, year, month)
            // Composite unique rule checked manually in withValidator() below
            // because Laravel's unique() rule doesn't support multi-column easily.

            'males'           => ['required', 'integer', 'min:0', 'max:9999'],
            'females'         => ['required', 'integer', 'min:0', 'max:9999'],
            'transferred_in'  => ['required', 'integer', 'min:0', 'max:9999'],
            'transferred_out' => ['required', 'integer', 'min:0', 'max:9999'],
            'no_pay_leave'    => ['required', 'integer', 'min:0', 'max:9999'],
            'remarks'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Post-rule validators for rules that can't be expressed as a simple array.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $codeId     = (int) $this->input('subject_code_id');
            $positionId = (int) $this->input('position_id');
            $year       = (int) $this->input('year');
            $month      = (int) $this->input('month');

            // ── Uniqueness check ──────────────────────────────────────────
            $entryId = $this->route('carder_entry')?->id
                ?? $this->route('entry')?->id;

            $duplicate = CarderMonthlyEntry::where('subject_code_id', $codeId)
                ->where('position_id',     $positionId)
                ->where('year',            $year)
                ->where('month',           $month)
                ->when($entryId, fn ($q) => $q->where('id', '!=', $entryId))
                ->exists();

            if ($duplicate) {
                $v->errors()->add(
                    'month',
                    'An entry for this subject code, position, and period already exists. Edit the existing entry instead.'
                );
            }

            // ── Deadline enforcement ──────────────────────────────────────
            if (CarderMonthlyEntry::isDeadlinePassed($year, $month)) {
                $deadline = CarderMonthlyEntry::deadlineFor($year, $month)->format('d M Y');
                $v->errors()->add(
                    'month',
                    "The submission deadline for this period was {$deadline}. Entries are now locked. Request an amendment if a correction is needed."
                );
            }

            // ── Total vs approved sanity check (soft warning stored in remarks) ─
            // We don't hard-block (approved carder may legitimately be exceeded
            // temporarily), but record a warning so the Planning Officer can see it.
            if ($codeId && $positionId && $year) {
                $approved = ApprovedCarder::forYear($year)
                    ->where('position_id', $positionId)
                    ->sum('approved_amount');

                if ($approved > 0) {
                    $total = (int) $this->input('males', 0)
                        + (int) $this->input('females', 0);

                    if ($total > $approved * 1.2) {
                        // Over 120% of approved — almost certainly a data error
                        $v->errors()->add(
                            'males',
                            "Total in-post ({$total}) is more than 20% above the approved carder for this position ({$approved}). Please verify the figures before submitting."
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'subject_code_id.in'   => 'You may only submit data for a subject code assigned to your account.',
            'position_id.required' => 'Select which position this entry is for.',
            'position_id.in'       => 'The selected position is not linked to the chosen subject code.',
            'males.max'            => 'Male count seems too high. Please check the figure.',
            'females.max'          => 'Female count seems too high. Please check the figure.',
        ];
    }
}
