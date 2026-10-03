<?php

return [
    'integration' => ['routes' => ['advanced-governance.integrations', 'advanced-governance.hrmis.*'], 'title' => 'HRMIS reconciliation', 'steps' => ['Choose the correct source before importing its CSV.', 'Compare incoming and local values with the authoritative personnel record.', 'Record a resolution or defer with a reason; importing a discrepancy does not decide which source is correct.'], 'translations' => []],
    'personnel_file' => ['routes' => ['advanced-governance.personnel-file'], 'title' => 'Personnel-file requirements', 'steps' => ['Review missing evidence by employee and document type.', 'Open the employee document section to add the verified source.', 'Configure mandatory requirements using their effective dates and applicable position scope.'], 'translations' => []],
    'bundles' => ['routes' => ['advanced-governance.case-bundles.*'], 'title' => 'Formal case bundles', 'steps' => ['Choose the employee and administrative case purpose.', 'Check the available evidence before generating the bundle.', 'Review the generated bundle before sending it through the formal process.'], 'translations' => []],
    'assurance' => ['routes' => ['advanced-governance.assurance*', 'enterprise.assurance*'], 'title' => 'Assurance and official exports', 'steps' => ['Set the reporting date and export scope.', 'Generate the snapshot and inspect totals and evidence coverage.', 'Use the required checking/sign-off process before treating the export as an issued official record.'], 'translations' => []],
    'rule_provisions' => ['routes' => ['advanced-governance.rules'], 'title' => 'Circular and service-minute provisions', 'steps' => ['Select the applicable version and authoritative source.', 'Enter the provision’s subject area, conditions and effective dates.', 'Review the resulting findings before activating or using the rule in administrative decisions.'], 'translations' => []],
    'provenance' => ['routes' => ['advanced-governance.provenance*'], 'title' => 'Field provenance', 'steps' => ['Select the employee and the exact field being evidenced.', 'Record its authoritative source and verified value.', 'A provenance entry documents the source; inspect the employee record to confirm the intended value is present.'], 'translations' => []],
    'features' => ['routes' => ['admin.features*'], 'title' => 'Feature switches and local AI', 'steps' => ['Enable only the modules the institution is ready to use.', 'For LAN AI, configure a private endpoint and model supported by the local service; leaving the endpoint empty uses offline assistance.', 'Test a summary or draft and inspect Audit Log for the actual execution mode and result.'], 'translations' => []],
    'settings' => ['routes' => ['admin.settings*'], 'title' => 'System settings', 'steps' => ['Review the setting description and current value.', 'Update dates, limits and workflow defaults for the institution.', 'Save, then check an affected screen to confirm the new behavior.'], 'translations' => []],
    'positions' => ['routes' => ['positions.*', 'position-groups.*', 'position-subcategories.*', 'position-grades.*'], 'title' => 'Positions and grades', 'steps' => ['Select the correct position group and parent position where applicable.', 'Use the official title/code and configure counting rules deliberately.', 'Check existing employee assignments before disabling or changing a reference entry.'], 'translations' => []],
    'subject_codes' => ['routes' => ['subject-codes.*'], 'title' => 'Subject codes', 'steps' => ['Create or select the official administrative subject code.', 'Review the assigned officers and position scope.', 'Check responsibility coverage before changing or disabling the code.'], 'translations' => []],
    'unit_setup' => ['routes' => ['units.*', 'unit-types.*'], 'title' => 'Units and unit types', 'steps' => ['Select the correct unit category and official unit name/code.', 'Review linked employees and establishment allocations.', 'Check the effects on unit reports before disabling a unit.'], 'translations' => []],
    'permissions' => ['routes' => ['users.*', 'categories.*', 'user-categories.*'], 'title' => 'Users and role permissions', 'steps' => ['Assign the correct user category, roles and subject/unit scope.', 'Grant access according to actual administrative responsibility.', 'Verify the user’s permitted screens after saving; use disable/reset actions through the authorised workflow.'], 'translations' => []],
    'network_access' => ['routes' => ['ip-allowlist.*'], 'title' => 'Network access rules', 'steps' => ['Enter a single allowed IP address or supported network range.', 'Verify that authorised hospital workstations remain covered.', 'Keep an existing valid administrator access path while changing network rules.'], 'translations' => []],
    'navigation' => ['routes' => ['nav-items.*'], 'title' => 'Navigation configuration', 'steps' => ['Select the target role and inspect its menu preview.', 'Check the destination route and visibility before enabling an item.', 'Hiding a menu entry does not replace the server’s role and scope checks.'], 'translations' => []],
    'movements' => ['routes' => ['transfer-records.*'], 'title' => 'Transfers', 'steps' => ['Select the employee and verify the sending/receiving unit or institution.', 'Record actual effective dates and the supporting order/reference.', 'Check the current posting and history after saving the movement.'], 'translations' => []],
    'unit_decisions' => ['routes' => ['unit-decision-support.*'], 'title' => 'Unit decision support', 'steps' => ['Select the unit and reporting period.', 'Review shortages and workload alongside verified staffing.', 'Use the evidence to prepare an officer decision; the recommendation does not change staff assignments.'], 'translations' => []],
    'incidents' => ['routes' => ['incidents.*'], 'title' => 'Incident reports', 'steps' => ['Enter the incident date, category and factual account.', 'Provide the relevant reference/evidence and keep the account limited to what is known.', 'Track the review status and add corrections through the permitted workflow.'], 'translations' => []],

    'dashboard' => [
        'routes' => ['dashboard*', 'executive.*', 'subject-officer.*'],
        'title' => 'Dashboard',
        'steps' => ['Check the reporting date and your role/unit scope before comparing totals.', 'Use the chart highlight control to inspect the largest value; open Data table for exact figures.', 'Open an action card to work on the underlying records. Dashboard totals do not approve an administrative action.'],
        'translations' => [
            'si' => 'ඔබගේ අංශය හා වාර්තා දිනය පරීක්ෂා කර දර්ශක සහ ප්‍රස්ථාර සමාලෝචනය කරන්න.',
            'ta' => 'உங்கள் பிரிவு மற்றும் அறிக்கைத் தேதியைச் சரிபார்த்து குறியீடுகளையும் வரைபடங்களையும் பார்வையிடுங்கள்.',
        ],
    ],
    'temporal' => [
        'routes' => ['advanced-governance.temporal'],
        'title' => 'Historical organisation',
        'steps' => ['Choose the as-at date and select Reconstruct to request a new historical snapshot.', 'The timeline shows the latest recorded events up to that date. Replay animates this displayed history only; it does not change the snapshot.', 'An empty timeline means no events were recorded. Use an authoritative source when adding a historical event.'],
        'translations' => [

        ],
    ],
    'forecast' => [
        'routes' => ['advanced-governance.forecast', 'workforce.forecast*', 'planning-intelligence.*'],
        'title' => 'Establishment forecast',
        'steps' => ['Set a start and end date; the end must be on or after the start.', 'Compare confirmed retirements and transfers separately from planning assumptions.', 'Check the selected period and underlying movements before using projected staffing in a proposal.'],
        'translations' => [

        ],
    ],
    'advanced' => [
        'routes' => ['advanced-governance.*'],
        'title' => 'Governance intelligence',
        'steps' => ['Refresh eligibility to evaluate recorded facts; findings require an officer’s decision.', 'Use the forecast and historical organisation sections to inspect different time perspectives.', 'Review source records and evidence before resolving HRMIS differences or creating an assurance export.'],
        'translations' => [

        ],
    ],
    'eligibility' => [
        'routes' => [],
        'title' => 'Administrative eligibility',
        'steps' => ['Refresh findings after correcting source records or rules.', 'Review target date, status and explanation for each finding.', 'A due finding is an advisory flag; confirmation, promotion or retirement still follows the authorised workflow.'],
        'translations' => [

        ],
    ],
    'ai' => [
        'routes' => ['ai-record-assistant.*'],
        'title' => 'Record assistant',
        'steps' => ['Select Summarise record to review facts already recorded for this employee.', 'The result identifies hospital LAN AI or the offline assistant. Check missing information against the official service record.', 'Each request and outcome is logged with an audit reference; summary text is not copied into the audit log.'],
        'translations' => [
            'si' => 'සාරාංශය නිල සේවා වාර්තාව සමඟ පරීක්ෂා කරන්න. සහායක භාවිතය විගණන සටහනට ඇතුළත් වේ.',
            'ta' => 'சுருக்கத்தை அதிகாரப்பூர்வ சேவைப் பதிவுடன் சரிபார்க்கவும். உதவியாளர் பயன்பாடு தணிக்கைப் பதிவில் சேர்க்கப்படும்.',
        ],
    ],
    'letters' => [
        'routes' => ['service-letters.*'],
        'title' => 'Service letters and AI drafts',
        'steps' => ['Select the employee, language and the appropriate template before generating a draft.', 'AI produces a preview. Review it, then select Use this draft if you want to replace the editable body.', 'Complete every placeholder and verify dates and authority before submitting through the approval workflow.'],
        'translations' => [
            'si' => 'සේවකයා, භාෂාව හා ආකෘතිය තෝරන්න. කෙටුම්පත භාවිතයට පෙර සමාලෝචනය කරන්න.',
            'ta' => 'பணியாளர், மொழி மற்றும் வார்ப்புருவைத் தேர்ந்தெடுக்கவும். வரைவைப் பயன்படுத்தும் முன் சரிபார்க்கவும்.',
        ],
    ],
    'letter_templates' => [
        'routes' => ['service-letter-templates.*', 'service-letter-letterheads.*'],
        'title' => 'Letter templates and letterheads',
        'steps' => ['Select the intended language and letter type.', 'Keep employee placeholders intact and check the preview and print layout.', 'Save the template before using it to generate a service letter.'],
        'translations' => [

        ],
    ],
    'employee' => [
        'routes' => ['employees.*'],
        'title' => 'Employee profile',
        'steps' => ['Check identity, position, unit and subject-code ownership before saving.', 'Complete the relevant profile sections; fields must not begin with whitespace.', 'Record service periods, grades and supporting evidence in their dedicated sections rather than replacing historical dates.'],
        'translations' => [
            'si' => 'අනන්‍යතාවය, තනතුර හා ඒකකය තහවුරු කර සේවක වාර්තාව සුරකින්න.',
            'ta' => 'அடையாளம், பதவி மற்றும் பிரிவைச் சரிபார்த்து பணியாளர் பதிவைச் சேமிக்கவும்.',
        ],
    ],
    'service' => [
        'routes' => ['employee-service-periods.*', 'employee-grades.*', 'promotions.*'],
        'title' => 'Service history and grades',
        'steps' => ['Record the effective dates, position and grade from the official source.', 'Review existing periods for gaps or overlaps before adding a new record.', 'Mark verification status only after checking the supporting document.'],
        'translations' => [

        ],
    ],
    'training' => [
        'routes' => ['employee-training.*', 'employee-competencies.*', 'employee-qualifications.*', 'employee-exam-records.*'],
        'title' => 'Training, qualifications and competency',
        'steps' => ['Choose the correct employee and record the course, qualification, registration or examination.', 'Use the actual completion/result and expiry dates where applicable.', 'Attach or reference evidence and review approaching renewals before recording completion.'],
        'translations' => [

        ],
    ],
    'documents' => [
        'routes' => ['employee-documents.*'],
        'title' => 'Employee documents',
        'steps' => ['Choose the correct document category and verify that the file belongs to the employee.', 'Check issue/expiry dates and upload the required evidence.', 'Use the document list to inspect what is missing or expired before completing a lifecycle action.'],
        'translations' => [

        ],
    ],
    'confirmation' => [
        'routes' => ['employee-confirmation.*', 'employee-lifecycle.*'],
        'title' => 'Employee lifecycle',
        'steps' => ['Check eligibility dates, service history and required documents.', 'Record the source reference and officer decision through the applicable form.', 'System findings assist review; they do not automatically confirm employment or approve a lifecycle event.'],
        'translations' => [

        ],
    ],
    'increments' => [
        'routes' => ['employee-increments.*', 'increments.*'],
        'title' => 'Salary increments',
        'steps' => ['Check the employee’s grade, increment date and recorded salary information.', 'Review any holds or outstanding requirements before preparing increment actions.', 'Verify the resulting date and amount before submitting or approving.'],
        'translations' => [

        ],
    ],
    'leave' => [
        'routes' => ['employee-leave-records.*', 'employee-interdictions.*'],
        'title' => 'Absence and service restrictions',
        'steps' => ['Select the record type and enter the actual start/end dates.', 'Use the official reference and check effects on service chronology.', 'Correct errors through the permitted update process so the record remains traceable.'],
        'translations' => [

        ],
    ],
    'imports' => [
        'routes' => ['employee-imports.*'],
        'title' => 'Employee import',
        'steps' => ['Upload the supported spreadsheet and map columns to employee fields.', 'Select a default position when the file does not contain a position column.', 'Review the preview and resolve validation errors before running the import.'],
        'translations' => [

        ],
    ],
    'completion' => [
        'routes' => ['employee-profile-completion.*', 'data-quality.*', 'duplicate-cases.*'],
        'title' => 'Data quality and completion',
        'steps' => ['Filter to the employee group you are responsible for.', 'Open the flagged record and verify missing or conflicting values against an authoritative source.', 'Recheck the report after correction; a completeness score is not proof that the record is accurate.'],
        'translations' => [

        ],
    ],
    'self_service' => [
        'routes' => ['employee-self-service.*', 'employee-change-requests.*'],
        'title' => 'Employee self-service',
        'steps' => ['Review your displayed record before requesting a correction.', 'Describe the exact change and provide evidence where requested.', 'Track the request outcome; submitting a correction does not immediately change the official record.'],
        'translations' => [

        ],
    ],
    'counts' => [
        'routes' => ['employee-counts.*', 'workforce.*', 'administrative-intelligence.*'],
        'title' => 'Workforce analysis',
        'steps' => ['Select the date, unit and position filters relevant to your question.', 'Compare counts using the same scope and time period.', 'Use chart highlights and exact data tables to inspect outliers before opening the underlying record.'],
        'translations' => [

        ],
    ],
    'carder' => [
        'routes' => ['carder-entries.*', 'entry-amendments.*'],
        'title' => 'Monthly cadre returns',
        'steps' => ['Select the reporting year/month and assigned subject code.', 'Check approved and available counts for every position before submitting.', 'After submission, use the verification or amendment workflow instead of overwriting the approved return.'],
        'translations' => [

        ],
    ],
    'approved' => [
        'routes' => ['approved-carders.*', 'unit-allocations.*', 'unit-position-bindings.*'],
        'title' => 'Approved cadre and allocations',
        'steps' => ['Check the approved year, unit and position before entering an allocation.', 'Use the authorised cadre reference and compare unit totals with the approved establishment.', 'Review the saved allocation before using it in vacancy or workforce reports.'],
        'translations' => [

        ],
    ],
    'review' => [
        'routes' => ['cadre-reviews.*'],
        'title' => 'Cadre review proposals',
        'steps' => ['Enter a clear proposal title and add each position once.', 'Justify reductions exceeding 20% and proposed zero allocations.', 'Review the draft totals and supporting reasons before submitting for approval.'],
        'translations' => [

        ],
    ],
    'interns' => [
        'routes' => ['intern-batches.*', 'current-intern-assignments.*', 'intern-selection.*'],
        'title' => 'Intern placements',
        'steps' => ['Select the correct batch and rotation period.', 'Review unit capacity, available slots and existing assignments before allocating.', 'Confirm the placement dates and use the authorised assignment workflow for changes.'],
        'translations' => [

        ],
    ],
    'responsibility' => [
        'routes' => ['hr-responsibilities.*', 'hr-intelligence.*', 'hr-escalations.*', 'acting-subject-officers.*', 'acting-appointments.*'],
        'title' => 'Officer responsibility and acting cover',
        'steps' => ['Check the subject-code/position scope and effective dates.', 'Review current ownership and handover information before accepting a responsibility.', 'Track outstanding actions during acting cover and preserve the handover record.'],
        'translations' => [

        ],
    ],
    'governance' => [
        'routes' => ['governance.*', 'governance-control.*', 'administrative-decisions.*', 'enterprise.*'],
        'title' => 'Governance and decisions',
        'steps' => ['Identify the applicable rule/version and supporting official reference.', 'Review evidence and record the responsible officer’s decision.', 'Use the audit and approval history to check who acted and when.'],
        'translations' => [

        ],
    ],
    'reports' => [
        'routes' => ['reports.*', 'official-reports.*', 'export-audit.*', 'presentation.*'],
        'title' => 'Reports and exports',
        'steps' => ['Set the date, unit and position scope before generating a report.', 'Check figures and headings in the preview before printing or exporting.', 'Official report preparation, checking and approval remain separate responsibilities.'],
        'translations' => [

        ],
    ],
    'audit' => [
        'routes' => ['audit-logs.*'],
        'title' => 'Audit trail and AI usage',
        'steps' => ['Filter by action; AI started, LAN AI completed, Offline assistant used and AI failed are separate events.', 'Match the reference on the AI response with the reference in audit details.', 'A started entry without an outcome can indicate an interrupted request. Logs contain metadata, not prompts or generated employee text.'],
        'translations' => [

        ],
    ],
    'circulars' => [
        'routes' => ['circulars.*'],
        'title' => 'Memos and circulars',
        'steps' => ['Search or filter by the relevant topic/date and open the actual circular.', 'Check the issuing authority and effective date before using it.', 'When uploading or distributing a circular, verify the file and intended recipient group.'],
        'translations' => [

        ],
    ],
    'sharing' => [
        'routes' => ['letters.*', 'letter-reviews.*'],
        'title' => 'Letter sharing and review',
        'steps' => ['Select the intended recipients and inspect the attached document or editable letter.', 'Use review comments and the submission workflow to request corrections.', 'Check the current version and approval state before issuing or downloading a final copy.'],
        'translations' => [

        ],
    ],
    'car_pass' => [
        'routes' => ['car-passes.*'],
        'title' => 'Car passes',
        'steps' => ['Select the eligible employee/post and check the vehicle information.', 'Review validity dates and the current application status.', 'Issue or print only after the required approval has been recorded.'],
        'translations' => [

        ],
    ],
    'vacancy' => [
        'routes' => ['vacancy-availability-letters.*', 'recruitment-vacancies.*', 'incoming-officers.*', 'retirement-projects.*'],
        'title' => 'Vacancies and workforce movements',
        'steps' => ['Check the approved establishment and confirmed movement dates.', 'Verify post availability before preparing recruitment or vacancy correspondence.', 'Review supporting records before issuing a letter or updating movement status.'],
        'translations' => [

        ],
    ],
    'setup' => [
        'routes' => ['units.*', 'position-groups.*', 'position-subcategories.*', 'position-grades.*', 'position-acting-allowance-rules.*', 'salary-scales.*', 'admin.*', 'users.*'],
        'title' => 'Administration and configuration',
        'steps' => ['Choose the relevant configuration section and review current values.', 'Apply the minimum role/scope required for each user; verify feature toggles before saving.', 'Changing reference data can affect employee forms and reports. Check a representative record after saving.'],
        'translations' => [

        ],
    ],
    'security' => [
        'routes' => ['mfa.*', 'change-password.*', 'e-signatures.*'],
        'title' => 'Account security',
        'steps' => ['Follow the instructions for the selected password, MFA or signature action.', 'Verify the confirmation step before leaving this page.', 'Keep passwords, recovery material and signature files private.'],
        'translations' => [

        ],
    ],
    'notifications' => [
        'routes' => ['notifications.*'],
        'title' => 'Notifications',
        'steps' => ['Open the notification to inspect the source record and its current status.', 'Complete the required action in that section.', 'Marking a notification as read does not complete the underlying administrative task.'],
        'translations' => [

        ],
    ],
];
