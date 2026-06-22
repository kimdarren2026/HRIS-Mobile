<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Company Expenses - HRIS Mobile App</title>
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
  <a href="{{ auth()->user()->role === 'admin_hr' ? '/admin/dashboard' : '/finance/dashboard' }}" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary flex-1 truncate">Expenses</h1>
  @can('create', \App\Models\CompanyExpense::class)
    <a href="{{ route('finance.expenses.create') }}" class="text-primary p-1">
      <span class="material-symbols-outlined">add</span>
    </a>
  @endcan
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 font-body-md text-body-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-success">check_circle</span>
      {{ session('success') }}
    </div>
  @endif

  {{-- Summary Cards --}}
  <div class="grid grid-cols-2 gap-unit-sm">
    @php
      $cards = [
        ['label' => 'Draft',     'count' => $summary['draft'],     'color' => 'text-gray-600',   'bg' => 'bg-gray-50'],
        ['label' => 'Submitted', 'count' => $summary['submitted'], 'color' => 'text-blue-700',   'bg' => 'bg-blue-50'],
        ['label' => 'Approved',  'count' => $summary['approved'],  'color' => 'text-orange-700', 'bg' => 'bg-orange-50'],
        ['label' => 'Paid',      'count' => $summary['paid'],      'color' => 'text-green-700',  'bg' => 'bg-green-50'],
      ];
    @endphp
    @foreach($cards as $card)
      <div class="{{ $card['bg'] }} rounded-xl border border-border p-3 flex flex-col gap-1">
        <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $card['label'] }}</p>
        <p class="font-headline-md text-headline-md {{ $card['color'] }}">{{ $card['count'] }}</p>
      </div>
    @endforeach
  </div>

  {{-- Filters --}}
  <form method="GET" action="{{ route('finance.expenses.index') }}" class="flex flex-col gap-unit-sm">
    <div class="grid grid-cols-2 gap-unit-sm">
      <select name="category" class="border border-border rounded-xl px-3 py-2.5 font-body-md text-body-md bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">All Categories</option>
        @foreach(\App\Models\CompanyExpense::CATEGORIES as $cat)
          <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ str_replace('_', ' ', $cat) }}</option>
        @endforeach
      </select>
      <select name="status" class="border border-border rounded-xl px-3 py-2.5 font-body-md text-body-md bg-white text-on-surface focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">All Statuses</option>
        @foreach(\App\Models\CompanyExpense::STATUSES as $st)
          <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
        @endforeach
      </select>
    </div>
    <div class="grid grid-cols-2 gap-unit-sm">
      <input type="date" name="from" value="{{ request('from') }}" placeholder="From"
        class="border border-border rounded-xl px-3 py-2.5 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
      <input type="date" name="to" value="{{ request('to') }}" placeholder="To"
        class="border border-border rounded-xl px-3 py-2.5 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
    </div>
    <button type="submit" class="w-full bg-primary text-white py-2.5 rounded-xl font-label-md text-label-md active:opacity-90">Filter</button>
  </form>

  {{-- Expense List --}}
  @forelse($expenses as $expense)
    @php
      $statusColor = match($expense->status) {
        'DRAFT'     => 'bg-gray-100 text-gray-600',
        'SUBMITTED' => 'bg-blue-100 text-blue-700',
        'APPROVED'  => 'bg-orange-100 text-orange-700',
        'REJECTED'  => 'bg-red-100 text-red-700',
        'PAID'      => 'bg-green-100 text-green-700',
        default     => 'bg-gray-100 text-gray-600',
      };
    @endphp
    <a href="{{ route('finance.expenses.show', $expense) }}"
       class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-2 active:opacity-80">
      <div class="flex justify-between items-start">
        <div class="flex-1 min-w-0">
          <p class="font-label-md text-label-md text-on-surface truncate">{{ $expense->title }}</p>
          <p class="font-label-sm text-label-sm text-on-surface-variant">{{ str_replace('_', ' ', $expense->category) }}</p>
        </div>
        <span class="{{ $statusColor }} px-2 py-1 rounded-full font-status-badge text-status-badge ml-2 flex-shrink-0">{{ $expense->status }}</span>
      </div>
      <div class="flex justify-between items-center">
        <p class="font-body-md text-body-md text-on-surface-variant">{{ $expense->expense_date->format('M d, Y') }}</p>
        <p class="font-label-md text-label-md text-primary">Rp {{ number_format($expense->amount, 0, ',', '.') }}</p>
      </div>
      <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $expense->expense_number }}</p>
    </a>
  @empty
    <div class="bg-white rounded-xl border border-border shadow-sm p-8 text-center text-on-surface-variant font-body-md text-body-md">
      No expenses found.
    </div>
  @endforelse

  {{ $expenses->links() }}

</main>

<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
  @php($navRole = auth()->user()->role)
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="{{ $navRole === 'finance' ? '/finance/dashboard' : '/admin/dashboard' }}">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm">Home</span>
  </a>
  @if(in_array($navRole, ['finance', 'super_admin']))
    <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/payroll/periods">
      <span class="material-symbols-outlined">receipt_long</span>
      <span class="font-label-sm text-label-sm">Payroll</span>
    </a>
  @endif
  <a class="flex flex-col items-center justify-center text-primary bg-secondary-fixed rounded-xl px-3 py-1 transition-transform active:scale-95 duration-150" href="{{ route('finance.expenses.index') }}">
    <span class="material-symbols-outlined">account_balance_wallet</span>
    <span class="font-label-sm text-label-sm">Expenses</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/profile">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm">Profile</span>
  </a>
</nav>

</body></html>
