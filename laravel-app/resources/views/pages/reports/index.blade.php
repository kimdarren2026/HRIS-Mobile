<!DOCTYPE html>

<html class="light" lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Laporan &amp; Analitik - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Tailwind Configuration -->
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                "danger": "#EF4444",
                "on-primary": "#ffffff",
                "secondary-container": "#6860ef",
                "primary": "#3525cd",
                "on-primary-container": "#dad7ff",
                "surface-variant": "#dce2f3",
                "tertiary-fixed": "#ffdbcc",
                "on-secondary": "#ffffff",
                "surface-container-highest": "#dce2f3",
                "secondary-fixed-dim": "#c3c0ff",
                "surface": "#F9FAFB",
                "on-secondary-fixed-variant": "#372abf",
                "border": "#E5E7EB",
                "inverse-on-surface": "#ebf1ff",
                "surface-container-lowest": "#ffffff",
                "tertiary-fixed-dim": "#ffb695",
                "success": "#10B981",
                "secondary-fixed": "#e3dfff",
                "on-surface": "#151c27",
                "surface-container-high": "#e2e8f8",
                "on-tertiary-fixed-variant": "#7b2f00",
                "on-tertiary-fixed": "#351000",
                "error": "#ba1a1a",
                "primary-fixed-dim": "#c3c0ff",
                "surface-container": "#e7eefe",
                "background": "#f9f9ff",
                "surface-bright": "#f9f9ff",
                "on-tertiary-container": "#ffd2be",
                "on-primary-fixed": "#0f0069",
                "primary-fixed": "#e2dfff",
                "on-tertiary": "#ffffff",
                "surface-container-low": "#f0f3ff",
                "on-background": "#151c27",
                "secondary": "#4e45d5",
                "inverse-primary": "#c3c0ff",
                "outline": "#777587",
                "on-error-container": "#93000a",
                "on-surface-variant": "#464555",
                "primary-container": "#4f46e5",
                "tertiary-container": "#a44100",
                "error-container": "#ffdad6",
                "tertiary": "#7e3000",
                "outline-variant": "#c7c4d8",
                "surface-tint": "#4d44e3",
                "surface-dim": "#d3daea",
                "warning": "#F59E0B",
                "on-secondary-fixed": "#100069",
                "on-error": "#ffffff",
                "on-secondary-container": "#fffbff",
                "inverse-surface": "#2a313d",
                "on-primary-fixed-variant": "#3323cc"
            },
            "borderRadius": {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
            },
            "spacing": {
                "unit-md": "16px",
                "unit-xl": "32px",
                "unit-sm": "8px",
                "container-margin": "16px",
                "unit-xs": "4px",
                "unit-lg": "24px",
                "card-gap": "12px"
            },
            "fontFamily": {
                "body-md": ["Inter"],
                "status-badge": ["Inter"],
                "label-md": ["Inter"],
                "headline-lg": ["Inter"],
                "label-sm": ["Inter"],
                "headline-md": ["Inter"],
                "body-lg": ["Inter"]
            },
            "fontSize": {
                "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}]
            }
          },
        },
      }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            max-width: 390px;
            margin: 0 auto;
            position: relative;
            min-height: 100vh;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .custom-shadow {
            box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.05);
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background text-on-background min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-24">
<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface dark:bg-surface-dim shadow-sm border-b border-outline-variant dark:border-outline flex justify-between items-center px-container-margin h-16">
<div class="flex items-center gap-3">
<button class="active:scale-95 duration-150 p-2 rounded-full hover:bg-surface-container-low transition-colors" onclick="window.location.href='/settings'">
<span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim">menu</span>
</button>
<h1 class="font-headline-md text-headline-md font-bold text-primary dark:text-primary-fixed-dim">Laporan &amp; Analitik</h1>
</div>
	@include('partials.notification-bell', [
	    'class' => 'relative active:scale-95 duration-150 p-2 rounded-full hover:bg-surface-container-low transition-colors text-primary dark:text-primary-fixed-dim',
	    'badgeClass' => 'absolute top-1 right-1 w-2 h-2 rounded-full bg-danger border-2 border-surface',
	])
</header>
<main class="pt-20 px-container-margin flex flex-col gap-unit-lg">
<!-- Modul laporan belum terhubung ke data nyata (Phase 57). Sengaja tidak
     menampilkan angka/grafik palsu — lihat CLAUDE Phase 57 roadmap untuk
     rencana implementasi laporan attendance & export Excel nyata. -->
<section class="bg-white p-unit-lg rounded-xl border border-border custom-shadow flex flex-col items-center text-center gap-3 mt-4">
<span class="material-symbols-outlined text-primary text-[48px]">construction</span>
<h2 class="font-headline-md text-headline-md text-on-surface">Modul laporan sedang dikembangkan</h2>
<p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
    Laporan kehadiran, cuti, dan penggajian berbasis data nyata — termasuk export ke Excel — belum tersedia di halaman ini.
    Fitur ini sedang dalam pengembangan pada fase berikutnya.
</p>
<span class="mt-1 text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700 px-3 py-1.5 rounded-full">Segera Hadir</span>
</section>
@php($role = auth()->user()->role)
<section class="bg-surface-container-low p-unit-md rounded-xl border border-outline-variant/40 flex items-start gap-2">
<span class="material-symbols-outlined text-outline text-[18px] mt-0.5">info</span>
<p class="font-body-md text-body-md text-on-surface-variant">
    @if($role !== 'finance')
    Sementara ini, data kehadiran dapat dilihat melalui <a href="/hr/approval-queue" class="text-primary underline">Persetujuan</a>, dan
    @endif
    data penggajian dapat dilihat melalui <a href="/payroll/periods" class="text-primary underline">Penggajian</a>.
</p>
</section>
</main>
<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 dark:bg-surface-dim/80 backdrop-blur-md border-t border-outline-variant dark:border-outline flex justify-around items-center h-[72px] pb-safe px-unit-sm shadow-sm">
@if($role === 'finance')
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-surface-variant px-3 py-1.5 hover:bg-surface-container dark:hover:bg-surface-container-high transition-all active:scale-90 duration-200" href="/finance/dashboard">
<span class="material-symbols-outlined">home</span>
<span class="font-label-md text-label-md">{{ __('common.nav_home') }}</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-surface-variant px-3 py-1.5 hover:bg-surface-container dark:hover:bg-surface-container-high transition-all active:scale-90 duration-200" href="/payroll/periods">
<span class="material-symbols-outlined">payments</span>
<span class="font-label-md text-label-md">{{ __('common.nav_payroll') }}</span>
</a>
@else
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-surface-variant px-3 py-1.5 hover:bg-surface-container dark:hover:bg-surface-container-high transition-all active:scale-90 duration-200" href="/admin/dashboard">
<span class="material-symbols-outlined">home</span>
<span class="font-label-md text-label-md">{{ __('common.nav_home') }}</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-surface-variant px-3 py-1.5 hover:bg-surface-container dark:hover:bg-surface-container-high transition-all active:scale-90 duration-200" href="/hr/employees">
<span class="material-symbols-outlined">groups</span>
<span class="font-label-md text-label-md">{{ __('common.nav_employees') }}</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-surface-variant px-3 py-1.5 hover:bg-surface-container dark:hover:bg-surface-container-high transition-all active:scale-90 duration-200" href="/hr/approval-queue">
<span class="material-symbols-outlined">rule</span>
<span class="font-label-md text-label-md">{{ __('common.nav_approvals') }}</span>
</a>
@endif
<a class="flex flex-col items-center justify-center bg-secondary-container dark:bg-secondary text-on-secondary-container dark:text-on-secondary rounded-xl px-3 py-1.5 active:scale-90 duration-200" href="/reports">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">assessment</span>
<span class="font-label-md text-label-md">{{ __('common.nav_reports') }}</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant dark:text-on-surface-variant px-3 py-1.5 hover:bg-surface-container dark:hover:bg-surface-container-high transition-all active:scale-90 duration-200" href="/profile">
<span class="material-symbols-outlined">person</span>
<span class="font-label-md text-label-md">{{ __('common.nav_profile') }}</span>
</a>
</nav>
</body></html>
