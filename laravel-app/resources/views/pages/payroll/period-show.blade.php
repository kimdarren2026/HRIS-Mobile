<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>{{ $payrollPeriod->name }} - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "secondary-fixed": "#e3dfff","surface-container-low": "#f0f3ff","inverse-surface": "#2a313d",
                  "surface-variant": "#dce2f3","surface": "#F9FAFB","surface-container-high": "#e2e8f8",
                  "surface-container-lowest": "#ffffff","on-surface": "#151c27","surface-container": "#e7eefe",
                  "warning": "#F59E0B","on-secondary": "#ffffff","background": "#f9f9ff",
                  "primary-fixed": "#e2dfff","on-surface-variant": "#464555","danger": "#EF4444",
                  "primary-container": "#4f46e5","surface-container-highest": "#dce2f3","surface-tint": "#4d44e3",
                  "on-primary-container": "#dad7ff","on-primary": "#ffffff","primary": "#3525cd",
                  "on-error": "#ffffff","success": "#10B981","border": "#E5E7EB",
                  "primary-fixed-dim": "#c3c0ff","secondary-container": "#6860ef","secondary": "#4e45d5",
                  "outline-variant": "#c7c4d8"
          },
          "borderRadius": {"DEFAULT": "0.25rem","lg": "0.5rem","xl": "0.75rem","full": "9999px"},
          "spacing": {"unit-xs": "4px","card-gap": "12px","unit-md": "16px","container-margin": "16px","unit-xl": "32px","unit-sm": "8px","unit-lg": "24px"},
          "fontFamily": {"label-md": ["Inter"],"label-sm": ["Inter"],"headline-md": ["Inter"],"body-lg": ["Inter"],"status-badge": ["Inter"],"headline-lg": ["Inter"],"body-md": ["Inter"]},
          "fontSize": {
            "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
            "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
            "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
            "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
            "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
            "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
            "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
          }
        },
      },
    }
  </script>
<style>
    body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex items-center px-container-margin gap-3">
  <a href="{{ route('payroll.periods.index') }}" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary truncate">{{ $payrollPeriod->name }}</h1>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 font-body-md text-body-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-success">check_circle</span>
      {{ session('success') }}
    </div>
  @endif

  {{-- Period Info Card --}}
  @php
    $badgeClass = match($payrollPeriod->status) {
        'DRAFT'            => 'bg-gray-100 text-gray-600',
        'CALCULATED'       => 'bg-blue-100 text-blue-700',
        'HR_REVIEW'        => 'bg-yellow-100 text-yellow-700',
        'FINANCE_APPROVAL' => 'bg-orange-100 text-orange-700',
        'LOCKED'           => 'bg-purple-100 text-purple-700',
        'PAID'             => 'bg-green-100 text-green-700',
        default            => 'bg-gray-100 text-gray-600',
    };
  @endphp
  <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <div class="flex justify-between items-start">
      <div>
        <p class="font-label-md text-label-md text-on-surface-variant">Period</p>
        <p class="font-body-md text-body-md text-on-surface">
          {{ $payrollPeriod->start_date->format('M d') }} – {{ $payrollPeriod->end_date->format('M d, Y') }}
        </p>
      </div>
      <span class="{{ $badgeClass }} px-3 py-1 rounded-full font-status-badge text-status-badge">
        {{ str_replace('_', ' ', $payrollPeriod->status) }}
      </span>
    </div>
    @if($payrollPeriod->pay_date)
    <div>
      <p class="font-label-md text-label-md text-on-surface-variant">Pay Date</p>
      <p class="font-body-md text-body-md text-on-surface">{{ $payrollPeriod->pay_date->format('M d, Y') }}</p>
    </div>
    @endif
    @if($payrollPeriod->calculated_at)
    <div>
      <p class="font-label-md text-label-md text-on-surface-variant">Calculated At</p>
      <p class="font-body-md text-body-md text-on-surface">{{ $payrollPeriod->calculated_at->format('M d, Y H:i') }}</p>
    </div>
    @endif
  </div>

  {{-- Summary Totals --}}
  <div class="grid grid-cols-3 gap-unit-sm">
    <div class="bg-white rounded-xl border border-border shadow-sm p-3 flex flex-col gap-1">
      <p class="font-label-sm text-label-sm text-on-surface-variant">Employees</p>
      <p class="font-headline-md text-headline-md text-on-surface">{{ $totals['employee_count'] }}</p>
    </div>
    <div class="bg-white rounded-xl border border-border shadow-sm p-3 flex flex-col gap-1">
      <p class="font-label-sm text-label-sm text-on-surface-variant">Gross Pay</p>
      <p class="font-label-md text-label-md text-on-surface">Rp {{ number_format($totals['gross_pay'] / 1000000, 1) }}M</p>
    </div>
    <div class="bg-white rounded-xl border border-border shadow-sm p-3 flex flex-col gap-1">
      <p class="font-label-sm text-label-sm text-on-surface-variant">Net Pay</p>
      <p class="font-label-md text-label-md text-primary">Rp {{ number_format($totals['net_pay'] / 1000000, 1) }}M</p>
    </div>
  </div>

  {{-- Actions: one button shown based on status + role --}}
  @php $role = auth()->user()->role; @endphp

  @if($payrollPeriod->status === 'DRAFT' && in_array($role, ['finance', 'super_admin']))
    <form method="POST" action="{{ route('payroll.periods.calculate', $payrollPeriod) }}"
      onsubmit="return confirm('Run payroll calculation for {{ addslashes($payrollPeriod->name) }}?')">
      @csrf
      <button type="submit" class="w-full bg-primary text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
        <span class="material-symbols-outlined">calculate</span>
        Run Calculation
      </button>
    </form>

  @elseif($payrollPeriod->status === 'CALCULATED' && in_array($role, ['admin_hr', 'super_admin']))
    <form method="POST" action="{{ route('payroll.periods.submit-hr-review', $payrollPeriod) }}"
      onsubmit="return confirm('Submit {{ addslashes($payrollPeriod->name) }} for HR review?')">
      @csrf
      <button type="submit" class="w-full bg-secondary text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
        <span class="material-symbols-outlined">send</span>
        Submit for HR Review
      </button>
    </form>

  @elseif($payrollPeriod->status === 'HR_REVIEW' && in_array($role, ['finance', 'super_admin']))
    <form method="POST" action="{{ route('payroll.periods.finance-approve', $payrollPeriod) }}"
      onsubmit="return confirm('Approve {{ addslashes($payrollPeriod->name) }} for payment?')">
      @csrf
      <button type="submit" class="w-full bg-warning text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
        <span class="material-symbols-outlined">thumb_up</span>
        Finance Approve
      </button>
    </form>

  @elseif($payrollPeriod->status === 'FINANCE_APPROVAL' && in_array($role, ['finance', 'super_admin']))
    <form method="POST" action="{{ route('payroll.periods.lock', $payrollPeriod) }}"
      onsubmit="return confirm('Lock {{ addslashes($payrollPeriod->name) }}? This cannot be undone.')">
      @csrf
      <button type="submit" class="w-full bg-purple-600 text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
        <span class="material-symbols-outlined">lock</span>
        Lock Payroll
      </button>
    </form>

  @elseif($payrollPeriod->status === 'LOCKED' && in_array($role, ['finance', 'super_admin']))
    <form method="POST" action="{{ route('payroll.periods.mark-paid', $payrollPeriod) }}"
      onsubmit="return confirm('Mark {{ addslashes($payrollPeriod->name) }} as paid?')">
      @csrf
      <button type="submit" class="w-full bg-success text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
        <span class="material-symbols-outlined">payments</span>
        Mark as Paid
      </button>
    </form>
  @endif

  {{-- Export CSV (finance + super_admin only) --}}
  @if(in_array(auth()->user()->role, ['finance', 'super_admin']))
    <a href="{{ route('payroll.periods.export', $payrollPeriod) }}"
       class="flex items-center justify-center gap-2 w-full bg-surface-container border border-border text-on-surface py-3 rounded-xl font-label-md text-label-md active:opacity-70 transition-opacity">
      <span class="material-symbols-outlined">download</span>
      Export CSV
    </a>
  @endif

  {{-- Employee Records --}}
  @if($payrollPeriod->payrollRecords->count() > 0)
  <section class="flex flex-col gap-unit-sm">
    <h2 class="font-headline-md text-headline-md text-on-surface">Employee Records</h2>
    @foreach($payrollPeriod->payrollRecords as $record)
      <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-2">
        <div class="flex justify-between items-start">
          <div>
            <p class="font-label-md text-label-md text-on-surface">{{ $record->employee->user?->name ?? 'N/A' }}</p>
            <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $record->employee->department?->name ?? '—' }}</p>
          </div>
          <div class="text-right">
            <p class="font-label-md text-label-md text-primary">Rp {{ number_format($record->net_salary, 0, ',', '.') }}</p>
            <p class="font-label-sm text-label-sm text-on-surface-variant">net</p>
          </div>
        </div>
        @if($record->attendance_days !== null)
        <div class="flex gap-unit-md text-label-sm font-label-sm text-on-surface-variant">
          <span><span class="material-symbols-outlined text-[14px] align-middle">calendar_today</span> {{ $record->attendance_days }}d attended</span>
          @if($record->leave_days > 0)
            <span><span class="material-symbols-outlined text-[14px] align-middle">beach_access</span> {{ $record->leave_days }}d leave</span>
          @endif
        </div>
        @endif
      </div>
    @endforeach
  </section>
  @else
    <div class="bg-white rounded-xl border border-border shadow-sm p-6 text-center text-on-surface-variant font-body-md text-body-md">
      No payroll records yet. Run calculation to generate records.
    </div>
  @endif

</main>

<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/finance/dashboard">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm">Home</span>
  </a>
  <a class="flex flex-col items-center justify-center text-primary bg-secondary-fixed rounded-xl px-3 py-1 transition-transform active:scale-95 duration-150" href="/payroll/periods">
    <span class="material-symbols-outlined">receipt_long</span>
    <span class="font-label-sm text-label-sm">Payroll</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/profile">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm">Profile</span>
  </a>
</nav>

</body></html>
