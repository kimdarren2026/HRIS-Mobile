<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Audit Log Detail - HRIS Mobile App</title>
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
    pre { white-space: pre-wrap; word-break: break-all; }
</style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex items-center px-container-margin gap-3">
  <a href="{{ route('audit-logs.index') }}" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary flex-1 truncate">Audit Detail</h1>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  {{-- Header Card --}}
  <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <div class="flex justify-between items-start">
      <div class="flex-1 min-w-0 pr-2">
        <p class="font-headline-md text-headline-md text-primary">{{ str_replace('_', ' ', $auditLog->action) }}</p>
        <p class="font-label-sm text-label-sm text-on-surface-variant mt-0.5">{{ ucfirst($auditLog->module) }} · #{{ $auditLog->id }}</p>
      </div>
      <p class="font-label-sm text-label-sm text-on-surface-variant text-right flex-shrink-0">
        {{ $auditLog->created_at->format('M d, Y') }}<br>{{ $auditLog->created_at->format('H:i:s') }}
      </p>
    </div>
    <p class="font-body-md text-body-md text-on-surface border-t border-border pt-3">{{ $auditLog->description }}</p>
  </div>

  {{-- Actor --}}
  <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-2">
    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Actor</p>
    <p class="font-body-md text-body-md text-on-surface">{{ $auditLog->user?->name ?? 'System / Unknown' }}</p>
    @if($auditLog->user)
      <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $auditLog->user->email }} · {{ $auditLog->user->role }}</p>
    @endif
  </div>

  {{-- Target --}}
  @if($auditLog->auditable_type)
    <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-2">
      <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Target</p>
      <p class="font-body-md text-body-md text-on-surface">{{ class_basename($auditLog->auditable_type) }} #{{ $auditLog->auditable_id }}</p>
    </div>
  @endif

  {{-- Request Info --}}
  <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-2">
    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Request</p>
    <div>
      <p class="font-label-sm text-label-sm text-on-surface-variant">IP Address</p>
      <p class="font-body-md text-body-md text-on-surface">{{ $auditLog->ip_address ?? '—' }}</p>
    </div>
    @if($auditLog->user_agent)
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant">User Agent</p>
        <p class="font-label-sm text-label-sm text-on-surface break-all">{{ Str::limit($auditLog->user_agent, 120) }}</p>
      </div>
    @endif
  </div>

  {{-- Before / After --}}
  @if($auditLog->old_values || $auditLog->new_values)
    <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
      <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Changes</p>

      @if($auditLog->old_values)
        <div>
          <p class="font-label-sm text-label-sm text-red-600 mb-1">Before</p>
          <pre class="bg-red-50 rounded-lg p-3 font-label-sm text-label-sm text-red-800 text-[11px]">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
      @endif

      @if($auditLog->new_values)
        <div>
          <p class="font-label-sm text-label-sm text-green-600 mb-1">After</p>
          <pre class="bg-green-50 rounded-lg p-3 font-label-sm text-label-sm text-green-800 text-[11px]">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
      @endif
    </div>
  @endif

  {{-- Legacy changes blob --}}
  @if($auditLog->changes && !$auditLog->old_values && !$auditLog->new_values)
    <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-2">
      <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Changes</p>
      <pre class="bg-surface-container rounded-lg p-3 font-label-sm text-label-sm text-on-surface text-[11px]">{{ json_encode($auditLog->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
  @endif

</main>

<x-audit-bottom-nav />

</body></html>
