<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Audit Logs - HRIS Mobile App</title>
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
  <a href="/admin/dashboard" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary flex-1 truncate">Audit Logs</h1>
  <span class="font-label-sm text-label-sm text-on-surface-variant">Super Admin</span>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  {{-- Filters --}}
  <form method="GET" action="{{ route('audit-logs.index') }}" class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-unit-sm">
    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Filters</p>

    <select name="module" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
      <option value="">All Modules</option>
      @foreach($modules as $m)
        <option value="{{ $m }}" @selected(request('module') === $m)>{{ ucfirst($m) }}</option>
      @endforeach
    </select>

    <select name="action" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
      <option value="">All Actions</option>
      @foreach($actions as $a)
        <option value="{{ $a }}" @selected(request('action') === $a)>{{ str_replace('_', ' ', $a) }}</option>
      @endforeach
    </select>

    <select name="user_id" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
      <option value="">All Actors</option>
      @foreach($users as $u)
        <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
      @endforeach
    </select>

    <div class="grid grid-cols-2 gap-unit-sm">
      <input type="date" name="from" value="{{ request('from') }}"
        class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      <input type="date" name="to" value="{{ request('to') }}"
        class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md text-sm focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="flex gap-unit-sm">
      <button type="submit" class="flex-1 bg-primary text-white py-2.5 rounded-lg font-label-md text-label-md">Apply</button>
      <a href="{{ route('audit-logs.index') }}" class="flex-1 text-center bg-surface-container-high text-on-surface py-2.5 rounded-lg font-label-md text-label-md">Reset</a>
    </div>
  </form>

  {{-- Count --}}
  <p class="font-label-sm text-label-sm text-on-surface-variant">
    {{ number_format($auditLogs->total()) }} record(s) — page {{ $auditLogs->currentPage() }} of {{ $auditLogs->lastPage() }}
  </p>

  {{-- Log List --}}
  @forelse($auditLogs as $log)
    <a href="{{ route('audit-logs.show', $log) }}"
       class="block bg-white rounded-xl border border-border shadow-sm p-4 hover:border-primary transition-colors">
      <div class="flex justify-between items-start gap-2">
        <div class="flex-1 min-w-0">
          <p class="font-label-md text-label-md text-primary truncate">{{ str_replace('_', ' ', $log->action) }}</p>
          <p class="font-body-md text-body-md text-on-surface truncate mt-0.5">{{ $log->description }}</p>
          <p class="font-label-sm text-label-sm text-on-surface-variant mt-1">
            {{ $log->user?->name ?? 'System' }} · {{ ucfirst($log->module) }}
          </p>
        </div>
        <div class="text-right flex-shrink-0">
          <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $log->created_at->format('M d') }}</p>
          <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $log->created_at->format('H:i') }}</p>
          @if($log->auditable_type)
            <span class="inline-block mt-1 bg-secondary-fixed text-primary px-2 py-0.5 rounded-full text-[10px] font-semibold">
              {{ class_basename($log->auditable_type) }}
            </span>
          @endif
        </div>
      </div>
    </a>
  @empty
    <div class="bg-white rounded-xl border border-border p-8 text-center">
      <span class="material-symbols-outlined text-[40px] text-on-surface-variant">manage_search</span>
      <p class="font-body-md text-body-md text-on-surface-variant mt-2">No audit logs found.</p>
    </div>
  @endforelse

  {{-- Pagination --}}
  @if($auditLogs->hasPages())
    <div class="flex justify-between items-center gap-unit-sm">
      @if($auditLogs->onFirstPage())
        <span class="flex-1 text-center py-2.5 rounded-lg bg-surface-container-high text-on-surface-variant font-label-md text-label-md opacity-50">Previous</span>
      @else
        <a href="{{ $auditLogs->previousPageUrl() }}" class="flex-1 text-center py-2.5 rounded-lg bg-surface-container-high text-on-surface font-label-md text-label-md">Previous</a>
      @endif

      @if($auditLogs->hasMorePages())
        <a href="{{ $auditLogs->nextPageUrl() }}" class="flex-1 text-center py-2.5 rounded-lg bg-primary text-white font-label-md text-label-md">Next</a>
      @else
        <span class="flex-1 text-center py-2.5 rounded-lg bg-primary/40 text-white font-label-md text-label-md opacity-50">Next</span>
      @endif
    </div>
  @endif

</main>

<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/admin/dashboard">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm">Home</span>
  </a>
  <a class="flex flex-col items-center justify-center text-primary bg-secondary-fixed rounded-xl px-3 py-1 transition-transform active:scale-95 duration-150" href="{{ route('audit-logs.index') }}">
    <span class="material-symbols-outlined">shield</span>
    <span class="font-label-sm text-label-sm">Audit</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/profile">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm">Profile</span>
  </a>
</nav>

</body></html>
