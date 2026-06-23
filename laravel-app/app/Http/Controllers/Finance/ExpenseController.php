<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CompanyExpense;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CompanyExpense::class);

        $query = CompanyExpense::with(['creator', 'approver', 'employee.user'])
            ->orderByDesc('expense_date');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('expense_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('expense_date', '<=', $request->to);
        }

        $expenses = $query->paginate(20)->withQueryString();

        $summary = [
            'draft'     => CompanyExpense::where('status', 'DRAFT')->count(),
            'submitted' => CompanyExpense::where('status', 'SUBMITTED')->count(),
            'approved'  => CompanyExpense::where('status', 'APPROVED')->count(),
            'paid'      => CompanyExpense::where('status', 'PAID')->count(),
        ];

        return view('pages.finance.expenses.index', compact('expenses', 'summary'));
    }

    public function create(): View
    {
        Gate::authorize('create', CompanyExpense::class);

        $employees = Employee::with('user')->orderBy('id')->get();

        return view('pages.finance.expenses.create', compact('employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', CompanyExpense::class);

        $data = $request->validate([
            'category'       => ['required', Rule::in(CompanyExpense::CATEGORIES)],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'expense_date'   => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'employee_id'    => ['nullable', 'exists:employees,id'],
            'cost_center'    => ['nullable', 'string', 'max:100'],
            'receipt'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $ext         = strtolower($request->file('receipt')->getClientOriginalExtension() ?: 'pdf');
            $receiptPath = sprintf('expenses/receipts/%s/%s.%s', now()->format('Y/m'), Str::uuid(), $ext);
            Storage::disk('local')->put($receiptPath, file_get_contents($request->file('receipt')->getRealPath()));
        }

        $expense = CompanyExpense::create([
            'expense_number' => $this->generateExpenseNumber(),
            'category'       => $data['category'],
            'title'          => $data['title'],
            'description'    => $data['description'] ?? null,
            'amount'         => $data['amount'],
            'expense_date'   => $data['expense_date'],
            'recipient_name' => $data['recipient_name'],
            'employee_id'    => $data['employee_id'] ?? null,
            'cost_center'    => $data['cost_center'] ?? null,
            'receipt_path'   => $receiptPath,
            'status'         => 'DRAFT',
            'created_by'     => auth()->id(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'create_expense',
            'expense',
            "Expense #{$expense->expense_number} created.",
            null,
            CompanyExpense::class,
            $expense->id,
            null,
            ['expense_number' => $expense->expense_number, 'title' => $expense->title, 'amount' => (string) $expense->amount, 'status' => 'DRAFT'],
        );

        return redirect()->route('finance.expenses.show', $expense)
            ->with('success', "Expense '{$expense->title}' created.");
    }

    public function show(CompanyExpense $expense): View
    {
        Gate::authorize('view', $expense);

        $expense->load(['creator', 'approver', 'rejecter', 'payer', 'employee.user']);

        return view('pages.finance.expenses.show', compact('expense'));
    }

    public function edit(CompanyExpense $expense): View
    {
        Gate::authorize('update', $expense);

        $employees = Employee::with('user')->orderBy('id')->get();

        return view('pages.finance.expenses.edit', compact('expense', 'employees'));
    }

    public function update(Request $request, CompanyExpense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);

        $data = $request->validate([
            'category'       => ['required', Rule::in(CompanyExpense::CATEGORIES)],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'expense_date'   => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'employee_id'    => ['nullable', 'exists:employees,id'],
            'cost_center'    => ['nullable', 'string', 'max:100'],
            'receipt'        => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $old = $expense->only(['title', 'amount', 'category', 'status']);

        $receiptPath = $expense->receipt_path;
        if ($request->hasFile('receipt')) {
            $ext         = strtolower($request->file('receipt')->getClientOriginalExtension() ?: 'pdf');
            $receiptPath = sprintf('expenses/receipts/%s/%s.%s', now()->format('Y/m'), Str::uuid(), $ext);
            Storage::disk('local')->put($receiptPath, file_get_contents($request->file('receipt')->getRealPath()));
        }

        $expense->update([
            'category'       => $data['category'],
            'title'          => $data['title'],
            'description'    => $data['description'] ?? null,
            'amount'         => $data['amount'],
            'expense_date'   => $data['expense_date'],
            'recipient_name' => $data['recipient_name'],
            'employee_id'    => $data['employee_id'] ?? null,
            'cost_center'    => $data['cost_center'] ?? null,
            'receipt_path'   => $receiptPath,
            'status'         => 'DRAFT',
            'rejection_note' => null,
            'rejected_by'    => null,
            'rejected_at'    => null,
        ]);

        AuditLogService::log(
            auth()->user(),
            'update_expense',
            'expense',
            "Expense #{$expense->expense_number} updated.",
            null,
            CompanyExpense::class,
            $expense->id,
            ['title' => $old['title'], 'amount' => (string) $old['amount'], 'status' => $old['status']],
            ['title' => $expense->title, 'amount' => (string) $expense->amount, 'status' => 'DRAFT'],
        );

        return redirect()->route('finance.expenses.show', $expense)
            ->with('success', "Expense '{$expense->title}' updated.");
    }

    public function submit(CompanyExpense $expense): RedirectResponse
    {
        Gate::authorize('submit', $expense);

        $old = $expense->only(['status']);
        $expense->update(['status' => 'SUBMITTED']);

        AuditLogService::log(
            auth()->user(),
            'submit_expense',
            'expense',
            "Expense #{$expense->expense_number} submitted for approval.",
            null,
            CompanyExpense::class,
            $expense->id,
            $old,
            ['status' => 'SUBMITTED'],
        );

        User::query()
            ->where('is_active', true)
            ->whereIn('role', ['finance', 'super_admin'])
            ->where('id', '!=', $expense->created_by)
            ->get()
            ->filter(fn (User $user) => Gate::forUser($user)->allows('approve', $expense))
            ->each(fn (User $user) => $this->notifications->create(
                $user,
                'Expense needs approval',
                'A company expense is waiting for finance review.',
                'expense',
                route('finance.expenses.show', $expense, false),
                $expense,
            ));

        return redirect()->route('finance.expenses.show', $expense)
            ->with('success', 'Expense submitted for approval.');
    }

    public function approve(CompanyExpense $expense): RedirectResponse
    {
        Gate::authorize('approve', $expense);

        $old = $expense->only(['status', 'approved_by']);

        $expense->update([
            'status'      => 'APPROVED',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'approve_expense',
            'expense',
            "Expense #{$expense->expense_number} approved.",
            null,
            CompanyExpense::class,
            $expense->id,
            $old,
            ['status' => 'APPROVED', 'approved_by' => auth()->id()],
        );

        $expense->loadMissing('creator');
        if ($expense->creator) {
            $this->notifications->create(
                $expense->creator,
                'Expense approved',
                'Your company expense has been approved.',
                'expense',
                route('finance.expenses.show', $expense, false),
                $expense,
            );
        }

        return redirect()->route('finance.expenses.show', $expense)
            ->with('success', 'Expense approved.');
    }

    public function reject(Request $request, CompanyExpense $expense): RedirectResponse
    {
        Gate::authorize('reject', $expense);

        $request->validate([
            'rejection_note' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $old = $expense->only(['status']);

        $expense->update([
            'status'         => 'REJECTED',
            'rejection_note' => $request->rejection_note,
            'rejected_by'    => auth()->id(),
            'rejected_at'    => now(),
        ]);

        AuditLogService::log(
            auth()->user(),
            'reject_expense',
            'expense',
            "Expense #{$expense->expense_number} rejected.",
            null,
            CompanyExpense::class,
            $expense->id,
            $old,
            ['status' => 'REJECTED', 'rejected_by' => auth()->id()],
        );

        $expense->loadMissing('creator');
        if ($expense->creator) {
            $this->notifications->create(
                $expense->creator,
                'Expense rejected',
                'Your company expense has been rejected.',
                'expense',
                route('finance.expenses.show', $expense, false),
                $expense,
            );
        }

        return redirect()->route('finance.expenses.show', $expense)
            ->with('success', 'Expense rejected.');
    }

    public function markPaid(Request $request, CompanyExpense $expense): RedirectResponse
    {
        Gate::authorize('markPaid', $expense);

        // Guard: prevent re-paying an already-paid expense (policy checks status=APPROVED,
        // but explicit guard ensures idempotency if called directly)
        abort_if($expense->status === 'PAID', 422, 'Expense has already been marked as paid.');

        $data = $request->validate([
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $old = $expense->only(['status']);

        $expense->update([
            'status'            => 'PAID',
            'paid_by'           => auth()->id(),
            'paid_at'           => now(),
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        AuditLogService::log(
            auth()->user(),
            'mark_expense_paid',
            'expense',
            "Expense #{$expense->expense_number} marked as paid. Ref: " . ($data['payment_reference'] ?? 'none') . '.',
            null,
            CompanyExpense::class,
            $expense->id,
            $old,
            ['status' => 'PAID', 'paid_by' => auth()->id(), 'payment_reference' => $data['payment_reference'] ?? null],
        );

        $expense->loadMissing('creator');
        if ($expense->creator) {
            $this->notifications->create(
                $expense->creator,
                'Expense paid',
                'Your company expense has been marked as paid.',
                'expense',
                route('finance.expenses.show', $expense, false),
                $expense,
            );
        }

        return redirect()->route('finance.expenses.show', $expense)
            ->with('success', 'Expense marked as paid. This records payment status only — no real bank transfer was initiated.');
    }

    public function receipt(CompanyExpense $expense): mixed
    {
        Gate::authorize('view', $expense);

        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return Storage::disk('local')->response($expense->receipt_path);
    }

    private function generateExpenseNumber(): string
    {
        $prefix = 'EXP-' . now()->format('Ym') . '-';
        $last   = CompanyExpense::where('expense_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('expense_number');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
