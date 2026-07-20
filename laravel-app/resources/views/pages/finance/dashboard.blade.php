<!DOCTYPE html>

<html class="light" lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Dasbor Penggajian Keuangan - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary": "#3525cd",
                        "surface-container-lowest": "#ffffff",
                        "success": "#10B981",
                        "tertiary-container": "#a44100",
                        "surface-dim": "#d3daea",
                        "surface-container-low": "#f0f3ff",
                        "on-secondary-fixed-variant": "#372abf",
                        "on-primary-fixed": "#0f0069",
                        "on-error-container": "#93000a",
                        "on-tertiary": "#ffffff",
                        "error": "#ba1a1a",
                        "tertiary-fixed-dim": "#ffb695",
                        "outline-variant": "#c7c4d8",
                        "on-error": "#ffffff",
                        "inverse-surface": "#2a313d",
                        "primary-container": "#4f46e5",
                        "secondary-fixed-dim": "#c3c0ff",
                        "tertiary": "#7e3000",
                        "secondary": "#4e45d5",
                        "primary-fixed-dim": "#c3c0ff",
                        "surface-container-high": "#e2e8f8",
                        "primary-fixed": "#e2dfff",
                        "on-tertiary-fixed": "#351000",
                        "on-tertiary-container": "#ffd2be",
                        "danger": "#EF4444",
                        "border": "#E5E7EB",
                        "outline": "#777587",
                        "on-primary-fixed-variant": "#3323cc",
                        "inverse-on-surface": "#ebf1ff",
                        "on-secondary": "#ffffff",
                        "on-surface-variant": "#464555",
                        "surface-container": "#e7eefe",
                        "on-primary": "#ffffff",
                        "error-container": "#ffdad6",
                        "surface": "#F9FAFB",
                        "on-primary-container": "#dad7ff",
                        "secondary-fixed": "#e3dfff",
                        "tertiary-fixed": "#ffdbcc",
                        "secondary-container": "#6860ef",
                        "on-background": "#151c27",
                        "on-secondary-fixed": "#100069",
                        "on-secondary-container": "#fffbff",
                        "surface-tint": "#4d44e3",
                        "warning": "#F59E0B",
                        "inverse-primary": "#c3c0ff",
                        "on-surface": "#151c27",
                        "surface-variant": "#dce2f3",
                        "on-tertiary-fixed-variant": "#7b2f00",
                        "surface-bright": "#f9f9ff",
                        "surface-container-highest": "#dce2f3",
                        "background": "#f9f9ff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "unit-xs": "4px",
                        "unit-sm": "8px",
                        "unit-md": "16px",
                        "unit-xl": "32px",
                        "unit-lg": "24px",
                        "container-margin": "16px",
                        "card-gap": "12px"
                    },
                    "fontFamily": {
                        "label-sm": ["Inter"],
                        "status-badge": ["Inter"],
                        "headline-md": ["Inter"],
                        "body-md": ["Inter"],
                        "label-md": ["Inter"],
                        "headline-lg": ["Inter"],
                        "body-lg": ["Inter"]
                    },
                    "fontSize": {
                        "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                        "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                        "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}]
                    }
                },
            },
        }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            user-select: none;
        }
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
<style>
        body {
            min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen max-w-[390px] mx-auto overflow-x-hidden">
<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
<!-- TODO Phase 4: connect action -->
<button class="text-primary active:scale-95 duration-100 p-2 rounded-full hover:bg-surface-container transition-colors">
<span class="material-symbols-outlined">menu</span>
</button>
<h1 class="font-headline-md text-headline-md font-bold text-primary">HRIS Mobile App</h1>
	@include('partials.notification-bell', [
	    'class' => 'relative text-primary active:scale-95 duration-100 p-2 rounded-full hover:bg-surface-container transition-colors',
	    'badgeClass' => 'absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center',
	])
</header>
<main class="pt-20 px-container-margin max-w-[390px] mx-auto pb-32">
<!-- Greeting Section -->
<section class="mb-unit-lg">
<h2 class="font-headline-lg text-headline-lg text-on-surface">Hai, {{ auth()->user()->name }}</h2>
<p class="font-body-md text-body-md text-on-surface-variant">{{ now()->translatedFormat('l, F d, Y') }}</p>
</section>
<!-- Summary Cards 2x2 Grid -->
<section class="grid grid-cols-2 gap-card-gap mb-unit-lg">
<div class="bg-surface border border-border p-unit-md rounded-xl shadow-sm">
<p class="font-label-sm text-label-sm text-on-surface-variant mb-1">Periode Terakhir</p>
<p class="font-headline-md text-headline-md text-primary truncate">{{ $latestPeriods->first()?->name ?? 'N/A' }}</p>
</div>
<div class="bg-surface border border-border p-unit-md rounded-xl shadow-sm">
<p class="font-label-sm text-label-sm text-on-surface-variant mb-1">Total Pegawai</p>
<p class="font-headline-md text-headline-md text-primary">{{ $totalEmployees }}</p>
</div>
<div class="bg-surface border border-border p-unit-md rounded-xl shadow-sm">
<p class="font-label-sm text-label-sm text-on-surface-variant mb-1">Menunggu Persetujuan</p>
<p class="font-headline-md text-headline-md text-warning">{{ $statusCounts['FINANCE_APPROVAL'] }}</p>
</div>
<div class="bg-surface border border-border p-unit-md rounded-xl shadow-sm">
<p class="font-label-sm text-label-sm text-on-surface-variant mb-1">Periode Terbayar</p>
<p class="font-headline-md text-headline-md text-success">{{ $statusCounts['PAID'] }}</p>
</div>
</section>
<!-- Primary Action -->
<section class="mb-unit-lg">
<button class="w-full py-4 bg-primary text-on-primary font-headline-md text-headline-md rounded-xl shadow-sm active:scale-[0.98] transition-transform flex justify-center items-center gap-2" onclick="window.location.href='/payroll/periods'">
<span class="material-symbols-outlined">payments</span>
                Proses Penggajian Saat Ini
            </button>
</section>
<!-- Payroll Status Flow Section - Revised to Vertical -->
<section class="mb-unit-lg">
<h3 class="font-label-md text-label-md text-on-surface-variant mb-4 uppercase tracking-widest">Alur Status Penggajian</h3>
<div class="bg-surface border border-border rounded-xl p-unit-md shadow-sm">
<div class="space-y-0">
@php
    $payrollStatusLabels = [
        'DRAFT'             => 'Draf',
        'CALCULATED'        => 'Terhitung',
        'HR_REVIEW'         => 'Tinjauan HR',
        'FINANCE_APPROVAL'  => 'Persetujuan Keuangan',
        'LOCKED'            => 'Terkunci',
        'PAID'              => 'Dibayar',
    ];
@endphp
<!-- Step 1: Draft (Completed) -->
<div class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-6 h-6 rounded-full bg-success/20 text-success flex items-center justify-center">
<span class="material-symbols-outlined text-[16px] font-bold">check</span>
</div>
<div class="w-0.5 h-6 bg-success/30"></div>
</div>
<div class="pb-4">
<p class="font-label-md text-label-md text-on-surface-variant">Draf</p>
</div>
</div>
<!-- Step 2: Calculated (Completed) -->
<div class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-6 h-6 rounded-full bg-success/20 text-success flex items-center justify-center">
<span class="material-symbols-outlined text-[16px] font-bold">check</span>
</div>
<div class="w-0.5 h-6 bg-success/30"></div>
</div>
<div class="pb-4">
<p class="font-label-md text-label-md text-on-surface-variant">Terhitung</p>
</div>
</div>
<!-- Step 3: HR Review (Completed) -->
<div class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-6 h-6 rounded-full bg-success/20 text-success flex items-center justify-center">
<span class="material-symbols-outlined text-[16px] font-bold">check</span>
</div>
<div class="w-0.5 h-6 bg-primary"></div>
</div>
<div class="pb-4">
<p class="font-label-md text-label-md text-on-surface-variant">Tinjauan HR</p>
</div>
</div>
<!-- Step 4: Finance Approval (Active) -->
<div class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-8 h-8 -ml-1 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-md z-10">
<span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">verified_user</span>
</div>
<div class="w-0.5 h-8 bg-outline-variant"></div>
</div>
<div class="pb-6">
<p class="font-headline-md text-headline-md text-primary -mt-1">Persetujuan Keuangan</p>
<p class="font-body-md text-body-md text-on-surface-variant">Menunggu konfirmasi Anda</p>
</div>
</div>
<!-- Step 5: Locked -->
<div class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-6 h-6 rounded-full bg-surface-container-high border border-outline flex items-center justify-center opacity-50">
<span class="material-symbols-outlined text-[16px]">lock</span>
</div>
<div class="w-0.5 h-6 bg-outline-variant"></div>
</div>
<div class="pb-4">
<p class="font-label-md text-label-md text-on-surface-variant opacity-50">Terkunci</p>
</div>
</div>
<!-- Step 6: Paid -->
<div class="flex gap-4">
<div class="flex flex-col items-center">
<div class="w-6 h-6 rounded-full bg-surface-container-high border border-outline flex items-center justify-center opacity-50">
<span class="material-symbols-outlined text-[16px]">done_all</span>
</div>
</div>
<div>
<p class="font-label-md text-label-md text-on-surface-variant opacity-50">Dibayar</p>
</div>
</div>
</div>
</div>
</section>
<!-- Payroll Status Summary -->
<section class="mb-unit-lg">
<h3 class="font-label-md text-label-md text-on-surface-variant mb-3 uppercase tracking-widest">Ringkasan Status</h3>
<div class="bg-surface border border-border rounded-xl p-unit-md shadow-sm grid grid-cols-3 gap-3">
@foreach ([['DRAFT','outline'],['CALCULATED','primary'],['HR_REVIEW','secondary'],['FINANCE_APPROVAL','warning'],['LOCKED','tertiary'],['PAID','success']] as [$status, $color])
<div class="text-center">
<p class="font-headline-md text-{{ $color }}">{{ $statusCounts[$status] }}</p>
<p class="font-label-sm text-label-sm text-on-surface-variant">{{ $payrollStatusLabels[$status] }}</p>
</div>
@endforeach
</div>
</section>
<!-- Recent Payroll Periods Section -->
<section class="mb-unit-xl">
<div class="flex justify-between items-center mb-4">
<h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-widest">Periode Penggajian Terbaru</h3>
<a class="text-primary font-label-md text-label-md hover:underline" href="/payroll/periods">{{ __('common.action_view_all') }}</a>
</div>
@if ($latestPeriods->isEmpty())
<p class="font-body-md text-body-md text-on-surface-variant text-center py-6">Belum ada periode penggajian.</p>
@else
<div class="space-y-card-gap">
@foreach ($latestPeriods as $period)
<div class="bg-surface border border-border rounded-xl p-unit-md shadow-sm">
<div class="flex justify-between items-start mb-3">
<div>
<h4 class="font-headline-md text-headline-md text-on-surface">{{ $period->name }}</h4>
<p class="font-body-md text-body-md text-on-surface-variant">{{ $period->start_date->translatedFormat('M d') }} - {{ $period->end_date->translatedFormat('M d, Y') }}</p>
</div>
@php
$badgeClass = match($period->status) {
    'PAID'             => 'bg-success/10 text-success',
    'LOCKED'           => 'bg-primary/10 text-primary',
    'FINANCE_APPROVAL' => 'bg-warning/10 text-warning',
    'HR_REVIEW'        => 'bg-secondary/10 text-secondary',
    default            => 'bg-surface-container-high text-on-surface-variant',
};
@endphp
<span class="px-3 py-1 {{ $badgeClass }} rounded-full font-status-badge text-status-badge">{{ $payrollStatusLabels[$period->status] }}</span>
</div>
<div class="grid grid-cols-2 gap-4 mb-3">
<div>
<p class="font-label-sm text-label-sm text-on-surface-variant">Data Pegawai</p>
<p class="font-body-lg text-body-lg font-semibold">{{ $period->payrollRecords()->count() }}</p>
</div>
<div>
<p class="font-label-sm text-label-sm text-on-surface-variant">Tanggal Bayar</p>
<p class="font-body-lg text-body-lg font-semibold">{{ $period->pay_date?->translatedFormat('M d, Y') ?? '—' }}</p>
</div>
</div>
<a class="block w-full py-2 text-center border border-primary text-primary font-label-md text-label-md rounded-lg active:scale-95 transition-transform" href="/payroll/periods/{{ $period->id }}">{{ __('common.action_view_details') }}</a>
</div>
@endforeach
</div>
@endif
</section>

@if(auth()->user()->employee)
<!-- Self-Service Section -->
<section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-4 flex flex-col gap-3">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-[20px]">person</span>
<h2 class="font-semibold text-on-surface" style="font-size:16px;line-height:24px;">Layanan Mandiri Saya</h2>
</div>
<div class="grid grid-cols-2 gap-3">
<a href="/attendance/checkin" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">schedule</span>
<span style="font-size:12px;font-weight:600;color:#464555;">Kehadiran Saya</span>
</a>
<a href="/leave/request" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">event_note</span>
<span style="font-size:12px;font-weight:600;color:#464555;">Cuti Saya</span>
</a>
<a href="/my/payroll" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">receipt_long</span>
<span style="font-size:12px;font-weight:600;color:#464555;">Slip Gaji Saya</span>
</a>
<a href="/my/profile" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">account_circle</span>
<span style="font-size:12px;font-weight:600;color:#464555;">Profil Saya</span>
</a>
</div>
</section>
@endif
</main>
<!-- BottomNavBar - Updated items -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center h-18 pb-safe px-2 bg-surface/80 border-t border-border backdrop-blur-md shadow-[0_-1px_2px_0_rgba(0,0,0,0.05)] py-2">
<a class="flex flex-col items-center justify-center bg-secondary-fixed text-on-secondary-fixed rounded-full px-4 py-1 active:scale-90 transition-transform duration-200" href="/finance/dashboard">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">home</span>
<span class="font-label-md text-label-md">{{ __('common.nav_home') }}</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-2 py-1 hover:text-primary active:scale-90 transition-transform duration-200" href="/payroll/periods">
<span class="material-symbols-outlined">payments</span>
<span class="font-label-md text-label-md">{{ __('common.nav_payroll') }}</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-2 py-1 hover:text-primary active:scale-90 transition-transform duration-200" href="/reports">
<span class="material-symbols-outlined">assessment</span>
<span class="font-label-md text-label-md">{{ __('common.nav_reports') }}</span>
</a>
	<a class="flex flex-col items-center justify-center text-on-surface-variant px-2 py-1 hover:text-primary active:scale-90 transition-transform duration-200 relative" href="{{ route('notifications.index') }}">
	<span class="material-symbols-outlined">notifications</span>
	@if(($unreadNotificationCount ?? 0) > 0)
	<span class="absolute -top-1 right-1 min-w-5 h-5 px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
	@endif
	<span class="font-label-md text-label-md">{{ __('common.nav_notifications') }}</span>
	</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-2 py-1 hover:text-primary active:scale-90 transition-transform duration-200" href="/profile">
<span class="material-symbols-outlined">account_circle</span>
<span class="font-label-md text-label-md">{{ __('common.nav_profile') }}</span>
</a>
</nav>
<script>
        // Simple interactive feedback for buttons
        document.querySelectorAll('button, a').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.classList.add('opacity-80');
            });
            btn.addEventListener('touchend', function() {
                this.classList.remove('opacity-80');
            });
        });
    </script>
</body></html>
