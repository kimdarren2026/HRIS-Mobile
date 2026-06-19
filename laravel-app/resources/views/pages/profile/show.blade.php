<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Employee Profile - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
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
<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                      "surface": "#F9FAFB",
                      "outline-variant": "#c7c4d8",
                      "warning": "#F59E0B",
                      "surface-bright": "#f9f9ff",
                      "primary": "#3525cd",
                      "inverse-on-surface": "#ebf1ff",
                      "on-primary-fixed": "#0f0069",
                      "surface-tint": "#4d44e3",
                      "surface-dim": "#d3daea",
                      "inverse-primary": "#c3c0ff",
                      "error-container": "#ffdad6",
                      "error": "#ba1a1a",
                      "surface-variant": "#dce2f3",
                      "surface-container-highest": "#dce2f3",
                      "on-tertiary-fixed": "#351000",
                      "secondary-fixed": "#e3dfff",
                      "danger": "#EF4444",
                      "on-surface": "#151c27",
                      "secondary-container": "#6860ef",
                      "tertiary-fixed-dim": "#ffb695",
                      "on-primary-container": "#dad7ff",
                      "on-primary": "#ffffff",
                      "on-secondary-fixed": "#100069",
                      "secondary": "#4e45d5",
                      "on-primary-fixed-variant": "#3323cc",
                      "on-secondary-fixed-variant": "#372abf",
                      "on-error": "#ffffff",
                      "on-secondary-container": "#fffbff",
                      "background": "#f9f9ff",
                      "secondary-fixed-dim": "#c3c0ff",
                      "inverse-surface": "#2a313d",
                      "on-surface-variant": "#464555",
                      "success": "#10B981",
                      "on-tertiary-fixed-variant": "#7b2f00",
                      "border": "#E5E7EB",
                      "surface-container-lowest": "#ffffff",
                      "surface-container-low": "#f0f3ff",
                      "on-error-container": "#93000a",
                      "tertiary": "#7e3000",
                      "surface-container-high": "#e2e8f8",
                      "tertiary-container": "#a44100",
                      "primary-container": "#4f46e5",
                      "on-tertiary-container": "#ffd2be",
                      "on-tertiary": "#ffffff",
                      "tertiary-fixed": "#ffdbcc",
                      "primary-fixed": "#e2dfff",
                      "surface-container": "#e7eefe",
                      "on-secondary": "#ffffff",
                      "primary-fixed-dim": "#c3c0ff",
                      "outline": "#777587",
                      "on-background": "#151c27"
              },
              "borderRadius": {
                      "DEFAULT": "0.25rem",
                      "lg": "0.5rem",
                      "xl": "0.75rem",
                      "full": "9999px"
              },
              "spacing": {
                      "unit-xl": "32px",
                      "unit-xs": "4px",
                      "unit-md": "16px",
                      "unit-lg": "24px",
                      "unit-sm": "8px",
                      "card-gap": "12px",
                      "container-margin": "16px"
              },
              "fontFamily": {
                      "label-sm": ["Inter"],
                      "body-md": ["Inter"],
                      "headline-md": ["Inter"],
                      "body-lg": ["Inter"],
                      "headline-lg": ["Inter"],
                      "status-badge": ["Inter"],
                      "label-md": ["Inter"]
              },
              "fontSize": {
                      "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                      "label-sm-mobile": ["10px", {"lineHeight": "12px", "fontWeight": "500"}],
                      "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                      "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                      "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                      "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                      "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                      "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}]
              }
            },
          },
        }
    </script>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-surface text-on-surface min-h-screen flex flex-col items-center">
<!-- Top App Bar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
<button aria-label="Back" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container transition-colors active:scale-95" onclick="window.location.href='/employee/dashboard'">
<span class="material-symbols-outlined text-primary">arrow_back</span>
</button>
<h1 class="text-headline-md font-headline-md font-bold text-primary">Employee Profile</h1>
<!-- TODO Phase 4: connect action -->
<button aria-label="Notifications" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">notifications</span>
</button>
</header>
<!-- Main Content Canvas -->
<main class="w-full max-w-[390px] pt-16 pb-28 px-container-margin overflow-y-auto overflow-x-hidden flex flex-col gap-unit-lg">
<!-- Profile Section Card -->
<section class="mt-unit-lg flex flex-col items-center text-center">
<div class="relative mb-4">
<div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-surface-container">
<img class="w-full h-full object-cover" data-alt="A professional headshot of Alex Rivers, a young male product designer with a friendly expression. He is wearing a clean charcoal polo shirt against a minimalist, soft-focus office background with warm natural lighting. The image style is polished and high-end corporate, with a slight indigo tint to match the UI branding." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDwG55OYz1RFQ9Pt5D_Yrp8WIa_hx1i8waU4HsIcMmqDTsVhFGJtTE5LMq7U2Tdm6TLC2_S_fySZBQuPRKcgKnpUWEnbdG5T7lPoPL1lRfgFILBPLnlTlakt8RADMw1_pUYVvz-2t2h0AhDi2zvj--9uD14X5lOufnSL0zZdFyQHB1Ie19Wp9-UJDBeCzI5i7jQAFsB8owzxaCADZuyHS5PHREIc8TbQyIHvYcDi7xiWx2nO7HCh7UHOCXjMtvlIV6cAtxzpyOHIqQ"/>
</div>
<div class="absolute bottom-1 right-1 bg-success text-white px-3 py-1 rounded-full border-2 border-white shadow-sm flex items-center justify-center gap-1">
<span class="font-status-badge text-status-badge uppercase">Active</span>
</div>
</div>
<h2 class="font-headline-lg text-headline-lg text-on-surface">Alex Rivers</h2>
<p class="font-label-md text-label-md text-outline tracking-wider mb-2">ID: HR-2024-089</p>
<div class="flex flex-col gap-1 items-center">
<span class="font-body-lg text-body-lg text-primary-container font-semibold">Product Designer</span>
<span class="font-body-md text-body-md text-on-surface-variant">IT &amp; Engineering</span>
</div>
</section>
<!-- Summary Cards Row (Horizontal Scroll) -->
<section class="flex gap-card-gap overflow-x-auto hide-scrollbar pb-1 -mx-container-margin px-container-margin">
<!-- Leave Balance -->
<div class="min-w-[120px] bg-white border border-border rounded-xl p-unit-md shadow-sm flex flex-col gap-1">
<span class="text-label-md font-label-md text-on-surface-variant">Leave Balance</span>
<span class="text-headline-md font-headline-md text-primary">12 Days</span>
</div>
<!-- Attendance -->
<div class="min-w-[120px] bg-white border border-border rounded-xl p-unit-md shadow-sm flex flex-col gap-1">
<span class="text-label-md font-label-md text-on-surface-variant">Attendance</span>
<span class="text-headline-md font-headline-md text-success">98%</span>
<span class="text-[10px] text-outline">This Month</span>
</div>
<!-- Latest Payslip -->
<div class="min-w-[120px] bg-white border border-border rounded-xl p-unit-md shadow-sm flex flex-col gap-1">
<span class="text-label-md font-label-md text-on-surface-variant">Latest Payslip</span>
<span class="text-headline-md font-headline-md text-warning">Paid</span>
<span class="text-[10px] text-outline">June 2026</span>
</div>
</section>
<!-- Profile Information List -->
<section class="flex flex-col gap-unit-md">
<h3 class="font-label-md text-label-md text-outline uppercase tracking-widest pl-1">General Information</h3>
<div class="bg-white rounded-xl border border-border overflow-hidden divide-y divide-border shadow-sm">
<!-- Email -->
<div class="p-unit-md flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">mail</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] text-outline font-semibold uppercase">Email</span>
<span class="text-body-md font-body-md text-on-surface">alex.rivers@company.com</span>
</div>
</div>
</div>
<!-- Phone -->
<div class="p-unit-md flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">call</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] text-outline font-semibold uppercase">Phone</span>
<span class="text-body-md font-body-md text-on-surface">+62 812 3456 7890</span>
</div>
</div>
</div>
<!-- Join Date -->
<div class="p-unit-md flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">calendar_today</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] text-outline font-semibold uppercase">Join Date</span>
<span class="text-body-md font-body-md text-on-surface">Jan 15, 2024</span>
</div>
</div>
</div>
<!-- NIK (Masked) -->
<div class="p-unit-md flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">badge</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] text-outline font-semibold uppercase">NIK</span>
<span class="text-body-md font-body-md text-on-surface">3275***********001</span>
</div>
</div>
</div>
<!-- Bank Account -->
<div class="p-unit-md flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
</div>
<div class="flex flex-col">
<span class="text-[10px] text-outline font-semibold uppercase">Bank Account</span>
<span class="text-body-md font-body-md text-on-surface">**** 4521</span>
</div>
</div>
</div>
</div>
</section>
<!-- Location Section -->
<section class="flex flex-col gap-unit-md">
<h3 class="font-label-md text-label-md text-outline uppercase tracking-widest pl-1">Work Location</h3>
<div class="bg-white rounded-xl border border-border overflow-hidden shadow-sm">
<!-- Map Preview -->
<div class="h-32 w-full relative">
<div class="w-full h-full bg-cover bg-center" data-alt="A clean, minimalist vector map illustration of Jakarta Sudirman central business district. The map uses a soft palette of light grays and indigos with primary blue highlights for major roads. A single primary-colored pin icon marks the Jakarta Headquarters location. The style is flat, professional, and integrates perfectly with a corporate HR dashboard." style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuDxtc3U5wzlTsjg-xofxPU6F_SXKXtV8Jc4kNNnbXUS3OdzlXfpYCiL_QQtVV3IpESceoI3t48BS1bZZjdI7Xn41Xe9Wp3kEzn46f0FGbEAwRRQfRJ3rgMZwIzKLjZo8-zqBSKtZuno3zyWOXWboxzqGhn2lSaRTR0VMIbNd_CjIOee8Q9hQuvsZThZxGzBZVEHqwGQlynqy4V8W4TqSOiQVLse81rRCsb6F7ei0J06r3VYskCiv1vvcr5zPwr_6ExGtBbUDt0e81k')"></div>
<div class="absolute inset-0 bg-gradient-to-t from-white/40 to-transparent"></div>
</div>
<div class="p-unit-md divide-y divide-border">
<div class="pb-unit-md flex flex-col gap-1">
<span class="text-[10px] text-outline font-semibold uppercase">Office</span>
<span class="text-body-md font-body-md text-on-surface">Jakarta Headquarters</span>
</div>
<div class="pt-unit-md flex flex-col gap-1">
<span class="text-[10px] text-outline font-semibold uppercase">Address</span>
<span class="text-body-md font-body-md text-on-surface">Jl. Sudirman No. 12, Jakarta Pusat</span>
</div>
</div>
</div>
</section>
<!-- Action Buttons -->
<section class="flex flex-col gap-unit-md pt-unit-md">
<!-- TODO Phase 4: connect action -->
<button class="w-full h-14 bg-primary text-white font-semibold rounded-xl shadow-lg active:scale-95 transition-transform duration-150 flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-[20px]">edit</span>
                Edit Profile
            </button>
<form action="{{ route('logout') }}" method="POST">
@csrf
<button class="w-full h-12 text-danger font-semibold rounded-xl active:opacity-70 transition-opacity flex items-center justify-center gap-2" type="submit">
<span class="material-symbols-outlined text-[20px]">logout</span>
                Logout
            </button>
</form>
</section>
</main>
<!-- Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 bg-surface border-t border-border backdrop-blur-md shadow-lg h-20">
<!-- Home -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="/employee/dashboard">
<span class="material-symbols-outlined" data-icon="home">home</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Home</span>
</a>
<!-- Attendance -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="/attendance/checkin">
<span class="material-symbols-outlined" data-icon="schedule">schedule</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Attendance</span>
</a>
<!-- Leave -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="/leave/history">
<span class="material-symbols-outlined" data-icon="event_note">event_note</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Leave</span>
</a>
<!-- Payslip -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="/payslip/detail">
<span class="material-symbols-outlined" data-icon="payments">payments</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Payslip</span>
</a>
<!-- Profile (Active) -->
<a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 active:scale-90 transition-all" href="/profile">
<span class="material-symbols-outlined" data-icon="person" style="font-variation-settings: 'FILL' 1;">person</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Profile</span>
</a>
</nav>
<!-- Interaction Script -->
<script>
        // Simple ripple-like effect for interaction
        document.querySelectorAll('button, a').forEach(el => {
            el.addEventListener('mousedown', function() {
                this.style.opacity = '0.7';
            });
            el.addEventListener('mouseup', function() {
                this.style.opacity = '1';
            });
            el.addEventListener('mouseleave', function() {
                this.style.opacity = '1';
            });
        });
    </script>
</body></html>
