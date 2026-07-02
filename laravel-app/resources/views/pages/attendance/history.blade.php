<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<title>Riwayat Presensi - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "secondary": "#4e45d5",
                        "danger": "#EF4444",
                        "surface-container-low": "#f0f3ff",
                        "on-secondary-container": "#fffbff",
                        "border": "#E5E7EB",
                        "surface-container-lowest": "#ffffff",
                        "error": "#ba1a1a",
                        "surface-tint": "#4d44e3",
                        "on-secondary-fixed-variant": "#372abf",
                        "on-surface-variant": "#464555",
                        "on-tertiary-container": "#ffd2be",
                        "on-primary-fixed": "#0f0069",
                        "inverse-primary": "#c3c0ff",
                        "surface-dim": "#d3daea",
                        "on-background": "#151c27",
                        "on-tertiary-fixed": "#351000",
                        "success": "#10B981",
                        "secondary-container": "#6860ef",
                        "outline-variant": "#c7c4d8",
                        "tertiary-fixed-dim": "#ffb695",
                        "secondary-fixed": "#e3dfff",
                        "on-tertiary-fixed-variant": "#7b2f00",
                        "background": "#f9f9ff",
                        "surface-container": "#e7eefe",
                        "tertiary-fixed": "#ffdbcc",
                        "inverse-surface": "#2a313d",
                        "primary-fixed-dim": "#c3c0ff",
                        "primary-fixed": "#e2dfff",
                        "outline": "#777587",
                        "on-primary": "#ffffff",
                        "on-error": "#ffffff",
                        "surface-variant": "#dce2f3",
                        "tertiary": "#7e3000",
                        "surface-container-highest": "#dce2f3",
                        "on-secondary": "#ffffff",
                        "surface-container-high": "#e2e8f8",
                        "inverse-on-surface": "#ebf1ff",
                        "warning": "#F59E0B",
                        "on-primary-container": "#dad7ff",
                        "on-error-container": "#93000a",
                        "error-container": "#ffdad6",
                        "secondary-fixed-dim": "#c3c0ff",
                        "primary": "#3525cd",
                        "tertiary-container": "#a44100",
                        "on-surface": "#151c27",
                        "surface": "#F9FAFB",
                        "on-primary-fixed-variant": "#3323cc",
                        "on-tertiary": "#ffffff",
                        "primary-container": "#4f46e5",
                        "on-secondary-fixed": "#100069",
                        "surface-bright": "#f9f9ff"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "unit-xs": "4px",
                        "unit-md": "16px",
                        "card-gap": "12px",
                        "unit-xl": "32px",
                        "unit-sm": "8px",
                        "container-margin": "16px",
                        "unit-lg": "24px"
                    },
                    "fontFamily": {
                        "label-sm": ["Inter"],
                        "body-lg": ["Inter"],
                        "headline-md": ["Inter"],
                        "headline-lg": ["Inter"],
                        "body-md": ["Inter"],
                        "status-badge": ["Inter"],
                        "label-md": ["Inter"]
                    },
                    "fontSize": {
                        "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "500" }],
                        "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "headline-lg": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                        "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "status-badge": ["12px", { "lineHeight": "12px", "fontWeight": "700" }],
                        "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }]
                    }
                }
            }
        }
    </script>
<style>
        /* Hide scrollbar for horizontal scrolling filter bar */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body-md antialiased min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-24">
<!-- Top App Bar (Mobile Default, Desktop Responsive via JSON definition logic) -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm dark:shadow-none hidden md:flex">
<div class="flex items-center gap-4">
<span class="material-symbols-outlined text-primary cursor-pointer hover:bg-surface-container p-2 rounded-full transition-colors active:scale-95 duration-150">menu</span>
<span class="text-headline-md font-headline-md font-bold text-primary">HRIS Mobile App</span>
</div>
	<div>
	@include('partials.notification-bell', [
	    'class' => 'relative text-primary cursor-pointer hover:bg-surface-container p-2 rounded-full transition-colors active:scale-95 duration-150 inline-flex',
	])
	</div>
</header>
<!-- Main Content Area -->
<main class="md:ml-0 md:pt-20 px-container-margin pt-unit-md pb-unit-lg max-w-[390px] mx-auto relative">
<!-- Header Section -->
<div class="mb-unit-lg">
<h1 class="text-headline-lg font-headline-lg text-on-background">Riwayat Presensi</h1>
<p class="text-body-md font-body-md text-on-surface-variant mt-1">Pantau catatan absen masuk dan absen pulang Anda.</p>
</div>
<!-- Top Summary Section (Bento-ish Grid) -->
<div class="grid grid-cols-2 gap-unit-sm mb-unit-lg">
<div class="col-span-2 bg-surface-container-lowest border border-border rounded-xl p-unit-md shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] flex items-center justify-between">
<div>
<p class="text-label-md font-label-md text-on-surface-variant mb-1 uppercase tracking-wider">Tingkat Kehadiran</p>
<p class="text-headline-lg font-headline-lg text-primary">98%</p>
</div>
<div class="h-12 w-12 rounded-full bg-primary-container/20 flex items-center justify-center">
<span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">trending_up</span>
</div>
</div>
<div class="bg-surface-container-lowest border border-border rounded-xl p-unit-md shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)]">
<p class="text-label-md font-label-md text-on-surface-variant mb-1 uppercase tracking-wider">Rata-rata Jam Kerja</p>
<p class="text-headline-md font-headline-md text-on-background">8.5h</p>
</div>
<div class="bg-surface-container-lowest border border-border rounded-xl p-unit-md shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)]">
<p class="text-label-md font-label-md text-on-surface-variant mb-1 uppercase tracking-wider">Menunggu Review HR</p>
<p class="text-headline-md font-headline-md text-warning">2</p>
</div>
</div>
<!-- Filter Section -->
<div class="flex gap-2 overflow-x-auto hide-scrollbar mb-unit-lg pb-1 -mx-container-margin px-container-margin md:mx-0 md:px-0">
<button class="whitespace-nowrap px-4 py-2 rounded-full bg-primary text-on-primary text-label-md font-label-md shadow-sm">Bulan Ini</button>
<button class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container-lowest border border-border text-on-surface-variant hover:bg-surface-container transition-colors text-label-md font-label-md">Bulan Lalu</button>
<button class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container-lowest border border-border text-on-surface-variant hover:bg-surface-container transition-colors text-label-md font-label-md flex items-center gap-1">
<span class="material-symbols-outlined text-[16px]">calendar_today</span>
                Rentang Kustom
            </button>
</div>
<x-flash-message />
<!-- Attendance Log (Dynamic Cards) -->
<div class="flex flex-col gap-unit-md">
@forelse($records as $record)
@php
$statusColor = match($record->status) {
    'APPROVED'       => 'bg-success',
    'PENDING_REVIEW' => 'bg-warning',
    'REJECTED'       => 'bg-danger',
    default          => 'bg-outline',
};
$badgeCls = match($record->status) {
    'APPROVED'       => 'bg-success/10 text-success',
    'PENDING_REVIEW' => 'bg-warning/10 text-warning',
    'REJECTED'       => 'bg-danger/10 text-danger',
    default          => 'bg-surface-container text-on-surface-variant',
};
$badgeIcon = match($record->status) {
    'APPROVED'       => 'check_circle',
    'PENDING_REVIEW' => 'pending',
    'REJECTED'       => 'cancel',
    default          => 'info',
};
$badgeLabel = match($record->status) {
    'APPROVED'       => 'Disetujui',
    'PENDING_REVIEW' => 'Menunggu Review HR',
    'REJECTED'       => 'Ditolak',
    default          => $record->status,
};
@endphp
<div class="bg-surface-container-lowest border border-border rounded-xl p-unit-md shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] flex flex-col gap-3 relative overflow-hidden">
<div class="absolute left-0 top-0 bottom-0 w-1 {{ $statusColor }} rounded-l-xl"></div>
<div class="flex justify-between items-start pl-2">
<div>
<h3 class="text-headline-md font-headline-md text-on-background">{{ $record->attendance_date->format('M d, Y') }}</h3>
<p class="text-label-sm font-label-sm text-on-surface-variant mt-0.5">{{ $record->attendance_date->format('l') }}</p>
</div>
<span class="px-2 py-1 rounded-full {{ $badgeCls }} text-status-badge font-status-badge uppercase tracking-wider flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">{{ $badgeIcon }}</span> {{ $badgeLabel }}
</span>
</div>
<div class="grid grid-cols-2 gap-4 mt-2 pl-2">
<div>
<p class="text-label-sm font-label-sm text-on-surface-variant mb-1 uppercase">Absen Masuk</p>
<p class="text-body-md font-body-md text-on-background font-medium">{{ $record->check_in_time ? $record->check_in_time->format('h:i A') : '--:--' }}</p>
</div>
<div>
<p class="text-label-sm font-label-sm text-on-surface-variant mb-1 uppercase">Absen Pulang</p>
<p class="text-body-md font-body-md text-on-background font-medium">{{ $record->check_out_time ? $record->check_out_time->format('h:i A') : '--:--' }}</p>
@if($record->check_out_lat && $record->check_out_lng)
<p class="text-label-sm font-label-sm text-on-surface-variant mt-0.5 opacity-70">{{ number_format((float)$record->check_out_lat, 5) }}, {{ number_format((float)$record->check_out_lng, 5) }}</p>
@endif
</div>
</div>
@if($record->status === 'PENDING_REVIEW' && $record->out_of_radius_reason)
<div class="pl-2 mt-1 bg-surface-container p-2 rounded-lg border border-warning/20 flex gap-2 items-start">
<span class="material-symbols-outlined text-warning text-[16px] mt-0.5">info</span>
<p class="text-label-sm font-label-sm text-on-surface-variant italic">"{{ $record->out_of_radius_reason }}"</p>
</div>
@endif
@if($record->status === 'REJECTED' && $record->approval_note)
<div class="pl-2 mt-1 bg-error-container/30 p-2 rounded-lg border border-danger/20 flex gap-2 items-start">
<span class="material-symbols-outlined text-danger text-[16px] mt-0.5">cancel</span>
<p class="text-label-sm font-label-sm text-on-surface-variant italic">Catatan HR: "{{ $record->approval_note }}"</p>
</div>
@endif
@if($record->check_in_photo_path)
<div class="pl-2 pt-2 border-t border-surface-variant">
<a href="/attendance/photo/{{ $record->id }}" target="_blank" class="inline-flex items-center gap-1 text-primary font-label-sm text-label-sm hover:underline">
<span class="material-symbols-outlined text-[14px]">photo_camera</span> Lihat selfie
</a>
</div>
@endif
</div>
@empty
<div class="flex flex-col items-center justify-center py-16 text-center select-none">
<div id="attendance-empty-anim" class="w-32 h-32" aria-hidden="true"></div>
<p class="font-body-md text-body-md text-on-surface-variant">Belum ada data presensi.</p>
<a href="/attendance/checkin" class="mt-4 text-primary font-label-md text-label-md hover:underline">Check in sekarang</a>
</div>
<script src="/assets/lottie/vendor/lottie-web.min.js"></script>
<script src="/assets/lottie/lottie-helper.js"></script>
<script>
    mountLottie('attendance-empty-anim', '/assets/lottie/empty-state.json', { loop: true, autoplay: true });
</script>
@endforelse
</div>
@if(method_exists($records, 'hasPages') && $records->hasPages())
<div class="mt-unit-lg">{{ $records->links() }}</div>
@endif
<!-- Spacer for Bottom Nav on Mobile -->
<div class="h-8"></div>
</main>
<!-- BottomNavBar (Mobile Only via layout rules) -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 bg-surface border-t border-border backdrop-blur-md shadow-lg dark:shadow-none">
<!-- Inactive: Home -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 active:scale-90 transition-all duration-200 cursor-pointer hover:bg-surface-container-high rounded-lg" href="/employee/dashboard">
<span class="material-symbols-outlined text-[24px]">home</span>
<span class="font-label-sm text-label-sm mt-1">Beranda</span>
</a>
<!-- Active: Attendance -->
<a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 active:scale-90 transition-all duration-200 cursor-pointer shadow-sm" href="/attendance/checkin">
<span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">schedule</span>
<span class="font-label-sm text-label-sm mt-1">Presensi</span>
</a>
<!-- Inactive: Leave -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 active:scale-90 transition-all duration-200 cursor-pointer hover:bg-surface-container-high rounded-lg" href="/leave/history">
<span class="material-symbols-outlined text-[24px]">event_note</span>
<span class="font-label-sm text-label-sm mt-1">Cuti</span>
</a>
<!-- Inactive: Payslip -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 active:scale-90 transition-all duration-200 cursor-pointer hover:bg-surface-container-high rounded-lg" href="/payslip/detail">
<span class="material-symbols-outlined text-[24px]">payments</span>
<span class="font-label-sm text-label-sm mt-1">Slip Gaji</span>
</a>
<!-- Inactive: Profile -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 active:scale-90 transition-all duration-200 cursor-pointer hover:bg-surface-container-high rounded-lg" href="{{ route('my.profile') }}">
<span class="material-symbols-outlined text-[24px]">person</span>
<span class="font-label-sm text-label-sm mt-1">Profil</span>
</a>
</nav>
<!-- Navigation Drawer (Desktop/Tablet Hidden structure for responsiveness context, optional render, leaving out to focus on canvas) -->
</body></html>
