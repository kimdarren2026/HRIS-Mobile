<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Employee Directory - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "surface":"#F9FAFB","surface-container-lowest":"#ffffff","surface-container-low":"#f0f3ff",
        "surface-container":"#e7eefe","surface-container-high":"#e2e8f8","surface-container-highest":"#dce2f3",
        "on-surface":"#151c27","on-surface-variant":"#464555","outline":"#777587","outline-variant":"#c7c4d8",
        "primary":"#3525cd","primary-container":"#4f46e5","primary-fixed":"#e2dfff","primary-fixed-dim":"#c3c0ff",
        "on-primary":"#ffffff","on-primary-container":"#dad7ff","inverse-primary":"#c3c0ff",
        "secondary":"#4e45d5","secondary-container":"#6860ef","on-secondary":"#ffffff","on-secondary-container":"#fffbff",
        "border":"#E5E7EB","background":"#f9f9ff","success":"#10B981","warning":"#F59E0B","danger":"#EF4444"
      },
      "borderRadius": {"DEFAULT":"0.25rem","lg":"0.5rem","xl":"0.75rem","full":"9999px"},
      "spacing": {"unit-xs":"4px","unit-sm":"8px","unit-md":"16px","unit-lg":"24px","unit-xl":"32px","card-gap":"12px","container-margin":"16px"},
      "fontFamily": {"label-sm":["Inter"],"label-md":["Inter"],"body-md":["Inter"],"body-lg":["Inter"],"headline-md":["Inter"],"headline-lg":["Inter"],"status-badge":["Inter"]},
      "fontSize": {
        "label-sm":["11px",{"lineHeight":"14px","fontWeight":"500"}],
        "label-md":["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}],
        "body-md":["14px",{"lineHeight":"20px","fontWeight":"400"}],
        "body-lg":["16px",{"lineHeight":"24px","fontWeight":"400"}],
        "headline-md":["20px",{"lineHeight":"28px","fontWeight":"600"}],
        "headline-lg":["24px",{"lineHeight":"32px","fontWeight":"700"}],
        "status-badge":["12px",{"lineHeight":"12px","fontWeight":"700"}]
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
</head>
<body class="bg-surface text-on-surface min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-24">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex justify-between items-center px-container-margin h-16">
  <div class="flex items-center gap-3">
    <a href="{{ route('payroll.periods.index') }}" class="text-on-surface-variant hover:bg-surface-container-low transition-colors p-2 rounded-full active:scale-95 duration-150">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="font-headline-md text-headline-md font-bold text-primary">Employee Directory</h1>
  </div>
  <a href="{{ route('employees.create') }}" class="text-primary hover:bg-surface-container-low p-2 rounded-full transition-colors active:scale-95">
    <span class="material-symbols-outlined">person_add</span>
  </a>
</header>

<main class="mt-16 px-container-margin pt-unit-md space-y-unit-md">

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 font-body-md text-body-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-success">check_circle</span>
      {{ session('success') }}
    </div>
  @endif

  {{-- Search & Filter --}}
  <form method="GET" action="{{ route('employees.index') }}" class="space-y-unit-sm">
    <div class="relative group">
      <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary transition-colors">search</span>
      <input name="search" value="{{ request('search') }}"
        class="w-full pl-12 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none font-body-md shadow-sm"
        placeholder="Search by name or NIK" type="text">
    </div>
    <div class="flex gap-3">
      <div class="flex-1 relative">
        <select name="department_id" onchange="this.form.submit()"
          class="w-full appearance-none bg-surface-container-lowest border border-outline-variant rounded-xl px-3 py-2.5 font-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
          @endforeach
        </select>
        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant text-[18px]">expand_more</span>
      </div>
      <div class="flex-1 relative">
        <select name="status" onchange="this.form.submit()"
          class="w-full appearance-none bg-surface-container-lowest border border-outline-variant rounded-xl px-3 py-2.5 font-body-md text-on-surface focus:ring-2 focus:ring-primary outline-none shadow-sm">
          <option value="">All Status</option>
          <option value="active"     {{ request('status') === 'active'     ? 'selected' : '' }}>Active</option>
          <option value="probation"  {{ request('status') === 'probation'  ? 'selected' : '' }}>Probation</option>
          <option value="resigned"   {{ request('status') === 'resigned'   ? 'selected' : '' }}>Resigned</option>
          <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
        </select>
        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant text-[18px]">expand_more</span>
      </div>
    </div>
    @if(request()->hasAny(['search','department_id','status']))
      <a href="{{ route('employees.index') }}" class="text-body-md text-primary underline">Clear filters</a>
    @endif
  </form>

  {{-- Employee Count --}}
  <p class="font-label-sm text-on-surface-variant">{{ $employees->total() }} employee{{ $employees->total() !== 1 ? 's' : '' }}</p>

  {{-- Employee Cards --}}
  <div class="space-y-card-gap">
    @forelse($employees as $emp)
      @php
        $statusColor = match($emp->employment_status) {
          'active'     => 'text-success bg-green-50',
          'probation'  => 'text-warning bg-yellow-50',
          'resigned'   => 'text-danger bg-red-50',
          'terminated' => 'text-on-surface-variant bg-surface-container',
          default      => 'text-on-surface-variant bg-surface-container',
        };
      @endphp
      <a href="{{ route('employees.show', $emp) }}"
        class="block bg-surface-container-lowest border border-outline-variant rounded-2xl p-unit-md shadow-sm hover:shadow-md transition-all">
        <div class="flex items-center gap-unit-md">
          <div class="w-12 h-12 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-lg shrink-0">
            {{ strtoupper(substr($emp->user?->name ?? '?', 0, 2)) }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <h3 class="font-headline-md text-on-surface text-[16px] truncate">{{ $emp->user?->name ?? '—' }}</h3>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $statusColor }} font-status-badge text-[11px] shrink-0 capitalize">
                {{ $emp->employment_status }}
              </span>
            </div>
            <p class="font-body-md text-on-surface-variant text-[13px] truncate">{{ $emp->position?->name ?? '—' }}</p>
            <div class="flex gap-4 mt-1">
              <span class="font-label-sm text-on-surface-variant">{{ $emp->department?->name ?? '—' }}</span>
              <span class="font-label-sm text-on-surface-variant">{{ $emp->nik }}</span>
            </div>
          </div>
          <span class="material-symbols-outlined text-on-surface-variant shrink-0">chevron_right</span>
        </div>
      </a>
    @empty
      <div class="text-center py-12 text-on-surface-variant">
        <span class="material-symbols-outlined text-[48px] block mb-2">group_off</span>
        <p class="font-body-md">No employees found.</p>
      </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  @if($employees->hasPages())
    <div class="flex justify-between items-center pt-2 pb-4">
      @if($employees->onFirstPage())
        <span class="px-4 py-2 text-on-surface-variant font-body-md opacity-50">Previous</span>
      @else
        <a href="{{ $employees->previousPageUrl() }}" class="px-4 py-2 bg-surface-container rounded-xl font-body-md text-primary">Previous</a>
      @endif
      <span class="font-label-sm text-on-surface-variant">Page {{ $employees->currentPage() }} of {{ $employees->lastPage() }}</span>
      @if($employees->hasMorePages())
        <a href="{{ $employees->nextPageUrl() }}" class="px-4 py-2 bg-surface-container rounded-xl font-body-md text-primary">Next</a>
      @else
        <span class="px-4 py-2 text-on-surface-variant font-body-md opacity-50">Next</span>
      @endif
    </div>
  @endif

</main>

<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-outline-variant shadow-sm flex justify-around items-center h-[72px] px-unit-sm">
  <a href="/admin/dashboard" class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-md text-label-md">Home</span>
  </a>
  <a href="{{ route('employees.index') }}" class="flex flex-col items-center justify-center bg-secondary-container text-on-secondary-container rounded-xl px-3 py-1.5 active:scale-90 duration-200">
    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">groups</span>
    <span class="font-label-md text-label-md">Employees</span>
  </a>
  <a href="/hr/approval-queue" class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
    <span class="material-symbols-outlined">rule</span>
    <span class="font-label-md text-label-md">Approvals</span>
  </a>
  <a href="/reports" class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
    <span class="material-symbols-outlined">assessment</span>
    <span class="font-label-md text-label-md">Reports</span>
  </a>
  <a href="/profile" class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-md text-label-md">Profile</span>
  </a>
</nav>

</body></html>
