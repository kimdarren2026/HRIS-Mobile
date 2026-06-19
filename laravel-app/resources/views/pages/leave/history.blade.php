<!DOCTYPE html><html lang="en" style=""><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Leave History - HRIS Mobile App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                      "surface-variant": "#dce2f3",
                      "surface-bright": "#f9f9ff",
                      "success": "#10B981",
                      "secondary-fixed": "#e3dfff",
                      "secondary-fixed-dim": "#c3c0ff",
                      "on-error": "#ffffff",
                      "on-secondary": "#ffffff",
                      "warning": "#F59E0B",
                      "on-primary-fixed-variant": "#3323cc",
                      "on-error-container": "#93000a",
                      "tertiary-container": "#a44100",
                      "on-surface": "#151c27",
                      "surface-dim": "#d3daea",
                      "primary-fixed": "#e2dfff",
                      "surface": "#F9FAFB",
                      "on-primary-container": "#dad7ff",
                      "inverse-primary": "#c3c0ff",
                      "secondary": "#4e45d5",
                      "tertiary": "#7e3000",
                      "on-tertiary-container": "#ffd2be",
                      "on-primary": "#ffffff",
                      "primary-container": "#4f46e5",
                      "surface-container-lowest": "#ffffff",
                      "tertiary-fixed": "#ffdbcc",
                      "on-tertiary-fixed-variant": "#7b2f00",
                      "outline-variant": "#c7c4d8",
                      "primary": "#3525cd",
                      "error": "#ba1a1a",
                      "danger": "#EF4444",
                      "on-secondary-fixed-variant": "#372abf",
                      "on-secondary-container": "#fffbff",
                      "on-surface-variant": "#464555",
                      "surface-container-high": "#e2e8f8",
                      "on-secondary-fixed": "#100069",
                      "tertiary-fixed-dim": "#ffb695",
                      "outline": "#777587",
                      "background": "#f9f9ff",
                      "primary-fixed-dim": "#c3c0ff",
                      "border": "#E5E7EB",
                      "on-tertiary": "#ffffff",
                      "surface-container-highest": "#dce2f3",
                      "error-container": "#ffdad6",
                      "on-background": "#151c27",
                      "on-tertiary-fixed": "#351000",
                      "inverse-surface": "#2a313d",
                      "inverse-on-surface": "#ebf1ff",
                      "on-primary-fixed": "#0f0069",
                      "surface-container-low": "#f0f3ff",
                      "secondary-container": "#6860ef",
                      "surface-tint": "#4d44e3",
                      "surface-container": "#e7eefe"
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
                      "container-margin": "16px",
                      "unit-xl": "32px",
                      "card-gap": "12px",
                      "unit-sm": "8px",
                      "unit-lg": "24px"
              },
              "fontFamily": {
                      "status-badge": [
                              "Inter"
                      ],
                      "headline-md": [
                              "Inter"
                      ],
                      "body-md": [
                              "Inter"
                      ],
                      "label-sm": [
                              "Inter"
                      ],
                      "headline-lg": [
                              "Inter"
                      ],
                      "label-md": [
                              "Inter"
                      ],
                      "body-lg": [
                              "Inter"
                      ]
              },
              "fontSize": {
                      "status-badge": [
                              "12px",
                              {
                                      "lineHeight": "12px",
                                      "fontWeight": "700"
                              }
                      ],
                      "headline-md": [
                              "20px",
                              {
                                      "lineHeight": "28px",
                                      "fontWeight": "600"
                              }
                      ],
                      "body-md": [
                              "14px",
                              {
                                      "lineHeight": "20px",
                                      "fontWeight": "400"
                              }
                      ],
                      "label-sm": [
                              "11px",
                              {
                                      "lineHeight": "14px",
                                      "fontWeight": "500"
                              }
                      ],
                      "headline-lg": [
                              "24px",
                              {
                                      "lineHeight": "32px",
                                      "fontWeight": "700"
                              }
                      ],
                      "label-md": [
                              "12px",
                              {
                                      "lineHeight": "16px",
                                      "letterSpacing": "0.05em",
                                      "fontWeight": "600"
                              }
                      ],
                      "body-lg": [
                              "16px",
                              {
                                      "lineHeight": "24px",
                                      "fontWeight": "400"
                              }
                      ]
              }
      },
          },
        }
      </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0;
        }
      </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
</head>
<body class="bg-surface font-body-md text-on-surface antialiased min-h-screen flex flex-col mx-auto max-w-[390px] relative pb-[88px]">
<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm mx-auto">
<!-- TODO Phase 4: connect action -->
<button class="text-on-surface-variant hover:bg-surface-container active:scale-95 transition-transform duration-150 p-2 rounded-full flex items-center justify-center">
<span class="material-symbols-outlined text-headline-md font-headline-md">menu</span>
</button>
<div class="text-headline-md font-headline-md font-bold text-primary whitespace-nowrap truncate">HRIS Mobile App</div>
<!-- TODO Phase 4: connect action -->
<button class="text-on-surface-variant hover:bg-surface-container active:scale-95 transition-transform duration-150 p-2 rounded-full flex items-center justify-center">
<span class="material-symbols-outlined text-headline-md font-headline-md">notifications</span>
</button>
</header>
<!-- Main Content Canvas -->
<main class="flex-grow pt-[88px] px-container-margin pb-unit-xl">
<!-- Header Section -->
<div class="mb-unit-lg">
<h1 class="font-headline-lg text-headline-lg text-on-surface mb-unit-xs">Leave History</h1>
<p class="font-body-md text-body-md text-on-surface-variant">Track your leave and permission requests.</p>
</div>
<!-- Filter Chips -->
<div class="flex overflow-x-auto gap-unit-sm mb-unit-lg pb-2 scrollbar-hide no-scrollbar -mx-container-margin px-container-margin" style="scrollbar-width: none; -ms-overflow-style: none;">
<style>
                .scrollbar-hide::-webkit-scrollbar { display: none; }
            </style>
<button class="whitespace-nowrap px-4 py-2 rounded-full bg-primary-container text-on-primary-container font-label-md text-label-md flex items-center justify-center">
                All
            </button>
<button class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container border border-outline-variant text-on-surface-variant font-label-md text-label-md flex items-center justify-center">
                Pending
            </button>
<button class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container border border-outline-variant text-on-surface-variant font-label-md text-label-md flex items-center justify-center">
                Approved
            </button>
<button class="whitespace-nowrap px-4 py-2 rounded-full bg-surface-container border border-outline-variant text-on-surface-variant font-label-md text-label-md flex items-center justify-center">
                Rejected
            </button>
</div>
<!-- Leave Cards List -->
<div class="flex flex-col gap-card-gap">
<!-- Card 1: Pending -->
<div class="bg-surface-lowest border border-border rounded-xl p-unit-md shadow-sm relative overflow-hidden bg-white">
<div class="absolute left-0 top-0 bottom-0 w-1 bg-warning"></div>
<div class="flex justify-between items-start mb-unit-sm pl-2">
<div class="flex items-center gap-unit-sm">
<div class="p-2 bg-surface-container rounded-lg flex items-center justify-center text-on-surface-variant">
<span class="material-symbols-outlined" data-icon="flight_takeoff">flight_takeoff</span>
</div>
<div>
<h3 class="font-label-md text-label-md text-on-surface">Annual Leave</h3>
<span class="font-status-badge text-status-badge text-warning bg-warning/10 px-2 py-1 rounded-full mt-1 inline-block">Pending HR</span>
</div>
</div>
<div class="text-right">
<div class="font-label-md text-label-md text-on-surface">3 Days</div>
</div>
</div>
<div class="pl-2">
<p class="font-body-md text-body-md text-on-surface-variant mb-1">Jun 20 - Jun 22, 2024</p>
<p class="font-body-md text-body-md text-on-surface-variant italic truncate">"Family vacation"</p>
</div>
</div>
<!-- Card 2: Approved -->
<div class="bg-surface-lowest border border-border rounded-xl p-unit-md shadow-sm relative overflow-hidden bg-white">
<div class="absolute left-0 top-0 bottom-0 w-1 bg-success"></div>
<div class="flex justify-between items-start mb-unit-sm pl-2">
<div class="flex items-center gap-unit-sm">
<div class="p-2 bg-surface-container rounded-lg flex items-center justify-center text-on-surface-variant">
<span class="material-symbols-outlined" data-icon="medical_services">medical_services</span>
</div>
<div>
<h3 class="font-label-md text-label-md text-on-surface">Sick Leave</h3>
<span class="font-status-badge text-status-badge text-success bg-success/10 px-2 py-1 rounded-full mt-1 inline-block">Approved</span>
</div>
</div>
<div class="text-right">
<div class="font-label-md text-label-md text-on-surface">1 Day</div>
</div>
</div>
<div class="pl-2">
<p class="font-body-md text-body-md text-on-surface-variant mb-1">May 15, 2024</p>
<p class="font-body-md text-body-md text-on-surface-variant italic truncate">"Dental appointment"</p>
</div>
</div>
<!-- Card 3: Rejected -->
<div class="bg-surface-lowest border border-border rounded-xl p-unit-md shadow-sm relative overflow-hidden bg-white">
<div class="absolute left-0 top-0 bottom-0 w-1 bg-error"></div>
<div class="flex justify-between items-start mb-unit-sm pl-2">
<div class="flex items-center gap-unit-sm">
<div class="p-2 bg-surface-container rounded-lg flex items-center justify-center text-on-surface-variant">
<span class="material-symbols-outlined" data-icon="work_off">work_off</span>
</div>
<div>
<h3 class="font-label-md text-label-md text-on-surface">Personal Leave</h3>
<span class="font-status-badge text-status-badge text-error bg-error/10 px-2 py-1 rounded-full mt-1 inline-block">Rejected</span>
</div>
</div>
<div class="text-right">
<div class="font-label-md text-label-md text-on-surface">5 Days</div>
</div>
</div>
<div class="pl-2">
<p class="font-body-md text-body-md text-on-surface-variant mb-1">Apr 10 - Apr 14, 2024</p>
<p class="font-body-md text-body-md text-on-surface-variant italic truncate">"Personal errand"</p>
</div>
</div>
</div>
</main>
<!-- FAB -->
<button class="fixed bottom-24 right-4 bg-primary-container text-on-primary shadow-lg rounded-full w-14 h-14 flex items-center justify-center hover:bg-primary transition-colors z-40 max-w-[390px] mx-auto left-auto right-4" onclick="window.location.href='/leave/request'" style="left: calc(50% + 195px - 72px);">
<span class="material-symbols-outlined" data-icon="add">add</span>
</button>
<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 bg-surface border-t border-border backdrop-blur-md shadow-lg mx-auto">
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="/employee/dashboard">
<span class="material-symbols-outlined mb-1" data-icon="home">home</span>
<span class="font-label-sm text-label-sm">Home</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="/attendance/checkin">
<span class="material-symbols-outlined mb-1" data-icon="schedule">schedule</span>
<span class="font-label-sm text-label-sm">Attendance</span>
</a>
<a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 active:scale-90 transition-all duration-200" href="/leave/history">
<span class="material-symbols-outlined mb-1" data-icon="event_note" data-weight="fill" style="font-variation-settings: 'FILL' 1;">event_note</span>
<span class="font-label-sm text-label-sm">Leave</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="/payslip/detail">
<span class="material-symbols-outlined mb-1" data-icon="payments">payments</span>
<span class="font-label-sm text-label-sm">Payslip</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="/profile">
<span class="material-symbols-outlined mb-1" data-icon="person">person</span>
<span class="font-label-sm text-label-sm">Profile</span>
</a>
</nav>


</body></html>
