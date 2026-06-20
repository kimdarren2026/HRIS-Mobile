<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Payroll Periods - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "secondary-fixed": "#e3dfff",
                  "surface-container-low": "#f0f3ff",
                  "on-tertiary-fixed": "#351000",
                  "inverse-surface": "#2a313d",
                  "surface-variant": "#dce2f3",
                  "surface": "#F9FAFB",
                  "on-tertiary-fixed-variant": "#7b2f00",
                  "surface-container-high": "#e2e8f8",
                  "surface-bright": "#f9f9ff",
                  "on-secondary-fixed-variant": "#372abf",
                  "surface-container-lowest": "#ffffff",
                  "on-surface": "#151c27",
                  "surface-container": "#e7eefe",
                  "warning": "#F59E0B",
                  "on-secondary": "#ffffff",
                  "on-primary-fixed": "#0f0069",
                  "secondary-fixed-dim": "#c3c0ff",
                  "background": "#f9f9ff",
                  "on-tertiary": "#ffffff",
                  "primary-fixed": "#e2dfff",
                  "inverse-on-surface": "#ebf1ff",
                  "on-secondary-container": "#fffbff",
                  "on-surface-variant": "#464555",
                  "danger": "#EF4444",
                  "primary-container": "#4f46e5",
                  "inverse-primary": "#c3c0ff",
                  "surface-container-highest": "#dce2f3",
                  "surface-tint": "#4d44e3",
                  "on-primary-container": "#dad7ff",
                  "tertiary-fixed": "#ffdbcc",
                  "on-primary-fixed-variant": "#3323cc",
                  "tertiary": "#7e3000",
                  "tertiary-container": "#a44100",
                  "surface-dim": "#d3daea",
                  "outline": "#777587",
                  "error": "#ba1a1a",
                  "on-background": "#151c27",
                  "on-primary": "#ffffff",
                  "primary": "#3525cd",
                  "on-secondary-fixed": "#100069",
                  "on-error": "#ffffff",
                  "success": "#10B981",
                  "on-tertiary-container": "#ffd2be",
                  "tertiary-fixed-dim": "#ffb695",
                  "border": "#E5E7EB",
                  "primary-fixed-dim": "#c3c0ff",
                  "error-container": "#ffdad6",
                  "on-error-container": "#93000a",
                  "secondary-container": "#6860ef",
                  "secondary": "#4e45d5",
                  "outline-variant": "#c7c4d8"
          },
          "borderRadius": {
                  "DEFAULT": "0.25rem",
                  "lg": "0.5rem",
                  "xl": "0.75rem",
                  "full": "9999px"
          },
          "spacing": {
                  "unit-xs": "4px",
                  "card-gap": "12px",
                  "unit-md": "16px",
                  "container-margin": "16px",
                  "unit-xl": "32px",
                  "unit-sm": "8px",
                  "unit-lg": "24px"
          },
          "fontFamily": {
                  "label-md": ["Inter"],
                  "label-sm": ["Inter"],
                  "headline-md": ["Inter"],
                  "body-lg": ["Inter"],
                  "status-badge": ["Inter"],
                  "headline-lg": ["Inter"],
                  "body-md": ["Inter"]
          },
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
    .custom-scrollbar::-webkit-scrollbar { display: none; }
    .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
</style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex justify-between items-center px-container-margin">
  <div class="flex items-center gap-3">
    <button class="transition-colors duration-200 active:opacity-70 text-primary p-1" onclick="window.location.href='/finance/dashboard'">
      <span class="material-symbols-outlined">menu</span>
    </button>
    <h1 class="font-headline-md text-headline-md font-bold text-primary">Payroll Periods</h1>
  </div>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 font-body-md text-body-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-success">check_circle</span>
      {{ session('success') }}
    </div>
  @endif
  @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 font-body-md text-body-md">
      <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- Summary Cards --}}
  <section class="grid grid-cols-2 gap-unit-sm">
    <div class="col-span-2 bg-white p-4 rounded-xl border border-border shadow-sm flex flex-col justify-between h-28 relative overflow-hidden">
      <div class="z-10">
        <p class="font-label-md text-label-md text-on-surface-variant mb-1">Total Periods</p>
        <h2 class="font-headline-lg text-headline-lg text-primary">{{ $summary['total_periods'] }}</h2>
      </div>
      <div class="z-10 flex items-center text-on-surface-variant font-label-sm text-label-sm gap-1">
        <span class="material-symbols-outlined text-[14px]">calendar_today</span>
        <span>{{ $summary['draft_count'] }} Draft · {{ $summary['calculated_count'] }} Calculated</span>
      </div>
      <div class="absolute -right-4 -bottom-4 text-surface-container-high scale-150">
        <span class="material-symbols-outlined text-[96px] opacity-10">receipt_long</span>
      </div>
    </div>
  </section>

  {{-- Create Period Button (finance/super_admin only) --}}
  @if(in_array(auth()->user()->role, ['finance', 'super_admin']))
  <section class="flex flex-col gap-unit-sm">
    <button id="toggleCreateForm" onclick="document.getElementById('createForm').classList.toggle('hidden')"
      class="w-full bg-primary-container text-on-primary py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:scale-[0.98] transition-transform">
      <span class="material-symbols-outlined">add_circle</span>
      Create New Period
    </button>

    {{-- Create Period Form --}}
    <div id="createForm" class="hidden bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-unit-md">
      <h3 class="font-headline-md text-headline-md text-on-surface">New Payroll Period</h3>
      <form method="POST" action="{{ route('payroll.periods.store') }}" class="flex flex-col gap-unit-md">
        @csrf
        <div class="flex flex-col gap-1">
          <label class="font-label-md text-label-md text-on-surface-variant">Period Name *</label>
          <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. July 2026 Payroll"
            class="w-full bg-white border border-border rounded-xl px-4 py-3 text-body-md font-body-md focus:ring-1 focus:ring-primary outline-none" required maxlength="100">
        </div>
        <div class="grid grid-cols-2 gap-unit-sm">
          <div class="flex flex-col gap-1">
            <label class="font-label-md text-label-md text-on-surface-variant">Start Date *</label>
            <input type="date" name="start_date" value="{{ old('start_date') }}"
              class="w-full bg-white border border-border rounded-xl px-3 py-3 text-body-md font-body-md focus:ring-1 focus:ring-primary outline-none" required>
          </div>
          <div class="flex flex-col gap-1">
            <label class="font-label-md text-label-md text-on-surface-variant">End Date *</label>
            <input type="date" name="end_date" value="{{ old('end_date') }}"
              class="w-full bg-white border border-border rounded-xl px-3 py-3 text-body-md font-body-md focus:ring-1 focus:ring-primary outline-none" required>
          </div>
        </div>
        <div class="flex flex-col gap-1">
          <label class="font-label-md text-label-md text-on-surface-variant">Pay Date (optional)</label>
          <input type="date" name="pay_date" value="{{ old('pay_date') }}"
            class="w-full bg-white border border-border rounded-xl px-4 py-3 text-body-md font-body-md focus:ring-1 focus:ring-primary outline-none">
        </div>
        <div class="grid grid-cols-2 gap-unit-sm pt-2">
          <button type="button" onclick="document.getElementById('createForm').classList.add('hidden')"
            class="border border-border text-on-surface-variant py-3 rounded-xl font-label-md text-label-md active:bg-surface-variant">
            Cancel
          </button>
          <button type="submit"
            class="bg-primary text-white py-3 rounded-xl font-label-md text-label-md active:opacity-90">
            Create
          </button>
        </div>
      </form>
    </div>
  </section>
  @endif

  {{-- Period List --}}
  <section class="flex flex-col gap-unit-md">
    @forelse($periods as $period)
      @php
        $recordCount = $period->payrollRecords->count();
        $totalNet    = $period->payrollRecords->sum(fn($r) => (float) $r->net_salary);
        $totalGross  = $period->payrollRecords->sum(fn($r) => (float) $r->basic_salary + (float) $r->allowance);

        $badgeClass = match($period->status) {
            'DRAFT'             => 'bg-gray-100 text-gray-600',
            'CALCULATED'        => 'bg-blue-100 text-blue-700',
            'HR_REVIEW'         => 'bg-yellow-100 text-yellow-700',
            'FINANCE_APPROVAL'  => 'bg-orange-100 text-orange-700',
            'LOCKED'            => 'bg-purple-100 text-purple-700',
            'PAID'              => 'bg-green-100 text-green-700',
            default             => 'bg-gray-100 text-gray-600',
        };
        $badgeLabel = match($period->status) {
            'DRAFT'             => 'Draft',
            'CALCULATED'        => 'Calculated',
            'HR_REVIEW'         => 'HR Review',
            'FINANCE_APPROVAL'  => 'Finance Approval',
            'LOCKED'            => 'Locked',
            'PAID'              => 'Paid',
            default             => $period->status,
        };
        $canCalculate = in_array(auth()->user()->role, ['finance', 'super_admin']) && $period->status === 'DRAFT';
        $canSubmitHR  = in_array(auth()->user()->role, ['admin_hr', 'super_admin']) && $period->status === 'CALCULATED';
      @endphp
      <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-4">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="font-headline-md text-headline-md text-on-surface mb-1">{{ $period->name }}</h3>
            <p class="font-body-md text-body-md text-on-surface-variant">
              {{ $period->start_date->format('M d') }} – {{ $period->end_date->format('M d, Y') }}
            </p>
          </div>
          <span class="{{ $badgeClass }} px-3 py-1 rounded-full font-status-badge text-status-badge">{{ $badgeLabel }}</span>
        </div>

        <div class="flex flex-col gap-1">
          <div class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px]">badge</span>
            {{ $recordCount }} {{ Str::plural('Employee', $recordCount) }}
          </div>
          @if($recordCount > 0)
          <div class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px]">payments</span>
            Total Net: Rp {{ number_format($totalNet, 0, ',', '.') }}
          </div>
          @endif
        </div>

        <div class="flex gap-2 pt-2 border-t border-border">
          <a href="{{ route('payroll.periods.show', $period) }}"
            class="flex-1 text-center bg-surface-container-low text-primary py-2 rounded-lg font-label-sm text-label-sm active:bg-surface-variant">
            View
          </a>
          @if($canCalculate)
            <form method="POST" action="{{ route('payroll.periods.calculate', $period) }}" class="flex-1"
              onsubmit="return confirm('Run payroll calculation for {{ addslashes($period->name) }}?')">
              @csrf
              <button type="submit" class="w-full bg-primary text-white py-2 rounded-lg font-label-sm text-label-sm active:opacity-90">
                Calculate
              </button>
            </form>
          @elseif($canSubmitHR)
            <form method="POST" action="{{ route('payroll.periods.submit-hr-review', $period) }}" class="flex-1">
              @csrf
              <button type="submit" class="w-full bg-secondary text-white py-2 rounded-lg font-label-sm text-label-sm active:opacity-90">
                Submit Review
              </button>
            </form>
          @endif
        </div>
      </div>
    @empty
      <div class="bg-white rounded-xl border border-border shadow-sm p-8 flex flex-col items-center gap-3 text-center">
        <span class="material-symbols-outlined text-[48px] text-on-surface-variant opacity-30">receipt_long</span>
        <p class="font-body-md text-body-md text-on-surface-variant">No payroll periods yet.</p>
        @if(in_array(auth()->user()->role, ['finance', 'super_admin']))
          <p class="font-label-sm text-label-sm text-on-surface-variant">Tap "Create New Period" to get started.</p>
        @endif
      </div>
    @endforelse
  </section>

  {{-- Pagination --}}
  @if($periods->hasPages())
    <div class="flex justify-center gap-unit-sm pt-2">
      @if($periods->onFirstPage())
        <span class="px-4 py-2 rounded-xl border border-border text-on-surface-variant font-label-md text-label-md opacity-40">Prev</span>
      @else
        <a href="{{ $periods->previousPageUrl() }}" class="px-4 py-2 rounded-xl border border-border text-primary font-label-md text-label-md">Prev</a>
      @endif
      @if($periods->hasMorePages())
        <a href="{{ $periods->nextPageUrl() }}" class="px-4 py-2 rounded-xl border border-border text-primary font-label-md text-label-md">Next</a>
      @else
        <span class="px-4 py-2 rounded-xl border border-border text-on-surface-variant font-label-md text-label-md opacity-40">Next</span>
      @endif
    </div>
  @endif

</main>

<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/finance/dashboard">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm">Home</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/hr/employees">
    <span class="material-symbols-outlined">badge</span>
    <span class="font-label-sm text-label-sm">Employees</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/hr/approval-queue">
    <span class="material-symbols-outlined">fact_check</span>
    <span class="font-label-sm text-label-sm">Approvals</span>
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

{{-- Auto-open form if validation failed --}}
@if($errors->any() && old('name'))
<script>document.getElementById('createForm').classList.remove('hidden');</script>
@endif

</body></html>
