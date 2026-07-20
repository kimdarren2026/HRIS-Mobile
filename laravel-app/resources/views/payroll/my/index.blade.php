<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Slip Gaji - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "secondary-fixed": "#e3dfff","surface-container-low": "#f0f3ff","on-tertiary-fixed": "#351000",
                  "inverse-surface": "#2a313d","surface-variant": "#dce2f3","surface": "#F9FAFB",
                  "surface-container-high": "#e2e8f8","surface-bright": "#f9f9ff","on-secondary-fixed-variant": "#372abf",
                  "surface-container-lowest": "#ffffff","on-surface": "#151c27","surface-container": "#e7eefe",
                  "warning": "#F59E0B","on-secondary": "#ffffff","on-primary-fixed": "#0f0069",
                  "secondary-fixed-dim": "#c3c0ff","background": "#f9f9ff","on-tertiary": "#ffffff",
                  "primary-fixed": "#e2dfff","inverse-on-surface": "#ebf1ff","on-secondary-container": "#fffbff",
                  "on-surface-variant": "#464555","danger": "#EF4444","primary-container": "#4f46e5",
                  "inverse-primary": "#c3c0ff","surface-container-highest": "#dce2f3","surface-tint": "#4d44e3",
                  "on-primary-container": "#dad7ff","tertiary-fixed": "#ffdbcc","on-primary-fixed-variant": "#3323cc",
                  "tertiary": "#7e3000","tertiary-container": "#a44100","surface-dim": "#d3daea",
                  "outline": "#777587","error": "#ba1a1a","on-background": "#151c27","on-primary": "#ffffff",
                  "primary": "#3525cd","on-secondary-fixed": "#100069","on-error": "#ffffff","success": "#10B981",
                  "on-tertiary-container": "#ffd2be","tertiary-fixed-dim": "#ffb695","border": "#E5E7EB",
                  "primary-fixed-dim": "#c3c0ff","error-container": "#ffdad6","on-error-container": "#93000a",
                  "secondary-container": "#6860ef","secondary": "#4e45d5","outline-variant": "#c7c4d8"
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
    .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
</style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex justify-between items-center px-container-margin">
  <div class="flex items-center gap-3">
    <a href="/employee/dashboard" class="text-primary p-1 transition-colors active:opacity-70">
      <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="font-headline-md text-headline-md font-bold text-primary">Slip Gaji</h1>
  </div>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  {{-- Payslip list --}}
  <section class="flex flex-col gap-unit-md">
    @forelse($records as $record)
      @php
        $period = $record->payrollPeriod;
        $badgeClass = match($period->status) {
            'CALCULATED'        => 'bg-blue-100 text-blue-700',
            'HR_REVIEW'         => 'bg-yellow-100 text-yellow-700',
            'FINANCE_APPROVAL'  => 'bg-orange-100 text-orange-700',
            'LOCKED'            => 'bg-purple-100 text-purple-700',
            'PAID'              => 'bg-green-100 text-green-700',
            default             => 'bg-gray-100 text-gray-600',
        };
        $badgeLabel = __('payroll.status_labels')[$period->status] ?? $period->status;
      @endphp
      <a href="{{ route('my.payroll.show', $record) }}"
         class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3 active:bg-surface-container-low transition-colors">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="font-headline-md text-headline-md text-on-surface mb-0.5">{{ $period->name }}</h3>
            <p class="font-body-md text-body-md text-on-surface-variant">
              {{ $period->start_date->translatedFormat('M d') }} – {{ $period->end_date->translatedFormat('M d, Y') }}
            </p>
          </div>
          <span class="{{ $badgeClass }} px-3 py-1 rounded-full font-status-badge text-status-badge">{{ $badgeLabel }}</span>
        </div>
        <div class="border-t border-border pt-3 flex justify-between items-center">
          <div>
            <p class="font-label-md text-label-md text-on-surface-variant mb-0.5">Gaji Bersih</p>
            <p class="font-headline-md text-headline-md text-primary">Rp {{ number_format((float) $record->net_salary, 0, ',', '.') }}</p>
          </div>
          <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
        </div>
      </a>
    @empty
      <div class="bg-white rounded-xl border border-border shadow-sm p-8 flex flex-col items-center gap-3 text-center">
        <span class="material-symbols-outlined text-[48px] text-on-surface-variant opacity-30">payments</span>
        <p class="font-body-md text-body-md text-on-surface-variant">Belum ada data gaji tersedia.</p>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Slip gaji akan muncul setelah payroll diproses.</p>
      </div>
    @endforelse
  </section>

  {{-- Pagination --}}
  @if($records->hasPages())
    <div class="flex justify-center gap-unit-sm pt-2">
      @if($records->onFirstPage())
        <span class="px-4 py-2 rounded-xl border border-border text-on-surface-variant font-label-md text-label-md opacity-40">Sebelumnya</span>
      @else
        <a href="{{ $records->previousPageUrl() }}" class="px-4 py-2 rounded-xl border border-border text-primary font-label-md text-label-md">Sebelumnya</a>
      @endif
      @if($records->hasMorePages())
        <a href="{{ $records->nextPageUrl() }}" class="px-4 py-2 rounded-xl border border-border text-primary font-label-md text-label-md">Berikutnya</a>
      @else
        <span class="px-4 py-2 rounded-xl border border-border text-on-surface-variant font-label-md text-label-md opacity-40">Berikutnya</span>
      @endif
    </div>
  @endif

</main>

<!-- BottomNavBar -->
<nav class="bg-surface fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 border-t border-border backdrop-blur-md mx-auto">
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/employee/dashboard">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_home') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/attendance/checkin">
    <span class="material-symbols-outlined">schedule</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_attendance') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/leave/history">
    <span class="material-symbols-outlined">event_note</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_leave') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/my/payroll">
    <span class="material-symbols-outlined">payments</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_payslip') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="{{ route('my.profile') }}">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_profile') }}</span>
  </a>
</nav>

</body></html>
