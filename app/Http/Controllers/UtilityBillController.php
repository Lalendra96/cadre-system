<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\Unit;
use App\Models\UtilityAccount;
use App\Models\UtilityBill;
use App\Models\UtilityBillPayment;
use App\Services\AuditLogService;
use App\Services\GovernanceAttestationService;
use App\Services\UtilityBillAccessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UtilityBillController extends Controller
{
    public function __construct(private UtilityBillAccessService $access) {}

    public function index(Request $request)
    {
        $assignment = $this->access->currentAssignment();
        $canManage = $this->access->canManage($request->user());
        $canViewAnalytics = $this->access->canViewAnalytics($request->user());
        $warningDays = max(1, SystemSetting::getInt('utility_bill_due_warning_days', 7));

        $query = UtilityBill::query()->with(['account.unit'])->latest('due_date')->latest('id');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('utility_type')) $query->whereHas('account', fn ($q) => $q->where('utility_type', $request->string('utility_type')));
        if ($request->filled('from')) $query->whereDate('bill_date', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('bill_date', '<=', $request->date('to'));
        $bills = $query->paginate(20)->withQueryString();

        $today = today();
        $summary = [
            'active_accounts' => UtilityAccount::query()->where('is_active', true)->count(),
            'unpaid_count' => UtilityBill::query()->whereIn('status', [UtilityBill::STATUS_UNPAID, UtilityBill::STATUS_PARTIAL])->count(),
            'overdue_count' => UtilityBill::query()->whereIn('status', [UtilityBill::STATUS_UNPAID, UtilityBill::STATUS_PARTIAL])->whereDate('due_date', '<', $today)->count(),
            'due_soon_count' => UtilityBill::query()->whereIn('status', [UtilityBill::STATUS_UNPAID, UtilityBill::STATUS_PARTIAL])->whereBetween('due_date', [$today, $today->copy()->addDays($warningDays)])->count(),
            'outstanding' => (float) UtilityBill::query()->whereIn('status', [UtilityBill::STATUS_UNPAID, UtilityBill::STATUS_PARTIAL])->selectRaw('COALESCE(SUM(bill_amount - amount_paid),0) total')->value('total'),
            'paid_this_month' => (float) UtilityBillPayment::query()->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('amount'),
        ];

        $analytics = $canViewAnalytics ? $this->analyticsData() : null;

        return view('utility-bills.index', compact('bills','assignment','canManage','canViewAnalytics','warningDays','summary','analytics'));
    }

    public function createAccount(Request $request)
    {
        $this->assertManage($request);
        $units = Unit::cachedActive();
        return view('utility-bills.account-form', compact('units'));
    }

    public function storeAccount(Request $request)
    {
        $this->assertManage($request);
        $data = $request->validate([
            'utility_type' => ['required','in:electricity,water,telephone,internet,sewerage,gas,other'],
            'provider_name' => ['required','string','max:150','not_regex:/^\\s/'],
            'account_no' => ['required','string','max:100','not_regex:/^\\s/'],
            'meter_no' => ['nullable','string','max:100','not_regex:/^\\s/'],
            'service_location' => ['required','string','max:200','not_regex:/^\\s/'],
            'unit_id' => ['nullable','integer','exists:units,id'],
            'contact_reference' => ['nullable','string','max:120','not_regex:/^\\s/'],
            'notes' => ['nullable','string','max:2000','not_regex:/^\\s/'],
            'administrative_purpose' => ['required','string','max:500','not_regex:/^\\s/'],
            'source_verified' => ['accepted'],
            'accuracy_confirmed' => ['accepted'],
        ]);
        $attestation = [
            'administrative_purpose' => $data['administrative_purpose'],
            'evidence_reviewed' => true,
            'accuracy_confirmed' => true,
            'minimum_necessary_confirmed' => true,
            'no_conflict_confirmed' => true,
        ];
        unset($data['administrative_purpose'], $data['source_verified'], $data['accuracy_confirmed']);
        $data['created_by'] = $request->user()->id;
        $account = UtilityAccount::create($data);
        AuditLogService::created($account, 'Created utility service account for bill monitoring.');
        GovernanceAttestationService::record($request, 'utility_account_created', 'UtilityAccount', $account->id, $attestation);
        return redirect()->route('utility-bills.index')->with('success', 'Utility account added with source-verification attestation.');
    }

    public function createBill(Request $request)
    {
        $this->assertManage($request);
        $accounts = UtilityAccount::query()->where('is_active', true)->orderBy('provider_name')->orderBy('account_no')->get();
        return view('utility-bills.bill-form', compact('accounts'));
    }

    public function storeBill(Request $request)
    {
        $this->assertManage($request);
        $data = $request->validate([
            'utility_account_id' => ['required','integer','exists:utility_accounts,id'],
            'bill_reference' => ['nullable','string','max:120','not_regex:/^\\s/'],
            'billing_period_start' => ['required','date'],
            'billing_period_end' => ['required','date','after_or_equal:billing_period_start'],
            'bill_date' => ['required','date'],
            'due_date' => ['required','date','after_or_equal:bill_date'],
            'previous_balance' => ['required','numeric','min:0','max:999999999999.99'],
            'current_charges' => ['required','numeric','min:0.01','max:999999999999.99'],
            'adjustments' => ['required','numeric','min:-999999999999.99','max:999999999999.99'],
            'bill_amount' => ['required','numeric','min:0.01','max:999999999999.99'],
            'consumption_value' => ['nullable','string','max:80','not_regex:/^\\s/'],
            'consumption_unit' => ['nullable','string','max:30','not_regex:/^\\s/'],
            'remarks' => ['nullable','string','max:2000','not_regex:/^\\s/'],
            'administrative_purpose' => ['required','string','max:500','not_regex:/^\\s/'],
            'source_verified' => ['accepted'],
            'accuracy_confirmed' => ['accepted'],
        ]);
        abort_unless(UtilityAccount::query()->whereKey($data['utility_account_id'])->where('is_active', true)->exists(), 422, 'The selected utility account is inactive.');
        $attestation = [
            'administrative_purpose' => $data['administrative_purpose'],
            'evidence_reviewed' => true,
            'accuracy_confirmed' => true,
            'minimum_necessary_confirmed' => true,
            'no_conflict_confirmed' => true,
        ];
        unset($data['administrative_purpose'], $data['source_verified'], $data['accuracy_confirmed']);
        $data['recorded_by'] = $request->user()->id;
        $data['amount_paid'] = 0;
        $data['status'] = UtilityBill::STATUS_UNPAID;
        $bill = UtilityBill::create($data);
        AuditLogService::created($bill, 'Recorded utility bill for due-date and payment monitoring.');
        GovernanceAttestationService::record($request, 'utility_bill_recorded', 'UtilityBill', $bill->id, $attestation);
        return redirect()->route('utility-bills.show', $bill)->with('success', 'Utility bill recorded with source-verification attestation.');
    }

    public function show(Request $request, UtilityBill $utilityBill)
    {
        $utilityBill->load(['account.unit','payments.recorder','recorder']);
        $canManage = $this->access->canManage($request->user());
        return view('utility-bills.show', ['bill' => $utilityBill, 'canManage' => $canManage]);
    }

    public function recordPayment(Request $request, UtilityBill $utilityBill)
    {
        $this->assertManage($request);
        $data = $request->validate([
            'payment_date' => ['required','date','before_or_equal:today'],
            'amount' => ['required','numeric','min:0.01','max:999999999999.99'],
            'payment_method' => ['required','in:bank_transfer,cheque,cash,online,other'],
            'payment_reference' => ['required','string','max:120','not_regex:/^\\s/'],
            'voucher_no' => ['nullable','string','max:100','not_regex:/^\\s/'],
            'remarks' => ['nullable','string','max:1000','not_regex:/^\\s/'],
            'administrative_purpose' => ['required','string','max:500','not_regex:/^\\s/'],
            'authority_reference' => ['required','string','max:180','not_regex:/^\\s/'],
            'payment_evidence_reviewed' => ['accepted'],
            'accuracy_confirmed' => ['accepted'],
            'no_duplicate_confirmed' => ['accepted'],
        ]);

        $attestation = [
            'administrative_purpose' => $data['administrative_purpose'],
            'authority_reference' => $data['authority_reference'],
            'evidence_reviewed' => true,
            'accuracy_confirmed' => true,
            'minimum_necessary_confirmed' => true,
            'no_conflict_confirmed' => true,
        ];
        $paymentData = $data;
        unset($paymentData['administrative_purpose'], $paymentData['authority_reference'], $paymentData['payment_evidence_reviewed'], $paymentData['accuracy_confirmed'], $paymentData['no_duplicate_confirmed']);

        DB::transaction(function () use ($utilityBill, $paymentData, $request, $attestation) {
            $bill = UtilityBill::query()->lockForUpdate()->findOrFail($utilityBill->id);
            $outstanding = max(0, (float) $bill->bill_amount - (float) $bill->amount_paid);
            abort_if((float) $paymentData['amount'] > $outstanding + 0.001, 422, 'Payment amount cannot exceed the outstanding bill balance.');
            $payment = UtilityBillPayment::create($paymentData + ['utility_bill_id' => $bill->id, 'recorded_by' => $request->user()->id]);
            $old = $bill->getOriginal();
            $newPaid = round((float) $bill->amount_paid + (float) $paymentData['amount'], 2);
            $status = $newPaid >= (float) $bill->bill_amount ? UtilityBill::STATUS_PAID : UtilityBill::STATUS_PARTIAL;
            $bill->update(['amount_paid' => $newPaid, 'status' => $status, 'updated_by' => $request->user()->id]);
            AuditLogService::created($payment, 'Recorded payment against a utility bill.');
            AuditLogService::updated($bill, $old, 'Updated utility bill payment status and balance.');
            GovernanceAttestationService::record($request, 'utility_payment_recorded', 'UtilityBillPayment', $payment->id, $attestation);
        });

        return back()->with('success', 'Payment recorded with governance attestation and outstanding balance updated.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless($this->access->canViewAnalytics($request->user()), 403);
        $rows = UtilityBill::query()->with('account')->orderByDesc('bill_date')->get();
        $filename = 'utility-bill-report-'.now()->format('Ymd-His').'.csv';
        AuditLogService::logExport($request->user()->id, 'utility_bill_csv', $filename, 'UtilityBill', null, null, $request->ip(), $request->userAgent());
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Type','Provider','Account No','Bill Ref','Period From','Period To','Bill Date','Due Date','Bill Amount','Paid','Outstanding','Status']);
            foreach ($rows as $bill) {
                fputcsv($out, [
                    $bill->account->utility_type,
                    $bill->account->provider_name,
                    $bill->account->account_no,
                    $bill->bill_reference,
                    $bill->billing_period_start?->format('Y-m-d'),
                    $bill->billing_period_end?->format('Y-m-d'),
                    $bill->bill_date?->format('Y-m-d'),
                    $bill->due_date?->format('Y-m-d'),
                    $bill->bill_amount,
                    $bill->amount_paid,
                    $bill->outstanding,
                    $bill->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function analyticsData(): array
    {
        $start = now()->startOfMonth()->subMonths(11);
        $monthly = UtilityBill::query()->whereDate('bill_date', '>=', $start)->selectRaw("TO_CHAR(bill_date, 'YYYY-MM') period, SUM(bill_amount) billed")->groupBy('period')->orderBy('period')->pluck('billed','period');
        $paid = UtilityBillPayment::query()->whereDate('payment_date', '>=', $start)->selectRaw("TO_CHAR(payment_date, 'YYYY-MM') period, SUM(amount) paid")->groupBy('period')->orderBy('period')->pluck('paid','period');
        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonths($i)->format('Y-m'));
        $trend = $months->map(fn ($month) => ['period' => Carbon::parse($month.'-01')->format('M Y'), 'billed' => (float) ($monthly[$month] ?? 0), 'paid' => (float) ($paid[$month] ?? 0)])->values();
        $byType = UtilityBill::query()
            ->join('utility_accounts', 'utility_accounts.id', '=', 'utility_bills.utility_account_id')
            ->whereDate('utility_bills.bill_date', '>=', now()->startOfYear())
            ->selectRaw('utility_accounts.utility_type, SUM(utility_bills.bill_amount) total')
            ->groupBy('utility_accounts.utility_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'type' => ucfirst(str_replace('_', ' ', $row->utility_type)),
                'total' => (float) $row->total,
            ]);
        return ['trend' => $trend, 'byType' => $byType];
    }

    private function assertManage(Request $request): void
    {
        abort_unless($this->access->canManage($request->user()), 403, 'Only the assigned Utility Bills Subject Officer can maintain utility accounts and payments.');
    }
}
