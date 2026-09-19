<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ServiceLetterTemplate;
use App\Services\CarderAiService;
use App\Services\FeatureToggleService;
use App\Services\WorkforceScopeService;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function employee(Request $request, Employee $employee, CarderAiService $ai)
    {
        WorkforceScopeService::authorizeEmployee($request->user(), $employee);
        abort_unless(FeatureToggleService::enabled('ai_record_assistant'),404);
        return response()->json($ai->employeeSummary($employee));
    }

    public function serviceLetter(Request $request, CarderAiService $ai)
    {
        abort_unless(FeatureToggleService::enabled('ai_service_letter_assistant'),404);
        $data=$request->validate([
            'employee_id'=>['required','integer','exists:employees,id'],'template_id'=>['nullable','integer','exists:service_letter_templates,id'],
            'purpose'=>['required','string','max:80'],'language'=>['required','in:en,si,ta'],'instructions'=>['nullable','string','max:500'],
        ]);
        $employee=Employee::with(['position','unit','gradeRecords.positionGrade'])->findOrFail($data['employee_id']);
        WorkforceScopeService::authorizeEmployee($request->user(),$employee);
        $template=!empty($data['template_id']) ? ServiceLetterTemplate::find($data['template_id']) : null;
        return response()->json($ai->draftServiceLetter($employee,$template,$data['purpose'],$data['language'],$data['instructions'] ?? ''));
    }
}
