<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Payslip Detail - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              "colors": {
                      "success": "#10B981",
                      "tertiary-fixed-dim": "#ffb695",
                      "secondary-fixed": "#e3dfff",
                      "border": "#E5E7EB",
                      "on-secondary-fixed-variant": "#372abf",
                      "surface-container-lowest": "#ffffff",
                      "inverse-on-surface": "#ebf1ff",
                      "on-tertiary-fixed": "#351000",
                      "error": "#ba1a1a",
                      "surface-container": "#e7eefe",
                      "background": "#f9f9ff",
                      "primary-fixed-dim": "#c3c0ff",
                      "on-surface": "#151c27",
                      "surface-container-high": "#e2e8f8",
                      "on-tertiary-fixed-variant": "#7b2f00",
                      "primary": "#3525cd",
                      "secondary-container": "#6860ef",
                      "surface-variant": "#dce2f3",
                      "on-primary-container": "#dad7ff",
                      "on-primary": "#ffffff",
                      "danger": "#EF4444",
                      "surface": "#F9FAFB",
                      "surface-container-highest": "#dce2f3",
                      "secondary-fixed-dim": "#c3c0ff",
                      "on-secondary": "#ffffff",
                      "tertiary-fixed": "#ffdbcc",
                      "surface-dim": "#d3daea",
                      "surface-tint": "#4d44e3",
                      "error-container": "#ffdad6",
                      "outline-variant": "#c7c4d8",
                      "tertiary": "#7e3000",
                      "on-secondary-container": "#fffbff",
                      "inverse-surface": "#2a313d",
                      "on-primary-fixed-variant": "#3323cc",
                      "on-error": "#ffffff",
                      "warning": "#F59E0B",
                      "on-secondary-fixed": "#100069",
                      "surface-container-low": "#f0f3ff",
                      "on-tertiary": "#ffffff",
                      "on-background": "#151c27",
                      "surface-bright": "#f9f9ff",
                      "primary-fixed": "#e2dfff",
                      "on-tertiary-container": "#ffd2be",
                      "on-primary-fixed": "#0f0069",
                      "on-error-container": "#93000a",
                      "tertiary-container": "#a44100",
                      "primary-container": "#4f46e5",
                      "on-surface-variant": "#464555",
                      "outline": "#777587",
                      "secondary": "#4e45d5",
                      "inverse-primary": "#c3c0ff"
              },
              "borderRadius": {
                      "DEFAULT": "0.25rem",
                      "lg": "0.5rem",
                      "xl": "0.75rem",
                      "full": "9999px"
              },
              "spacing": {
                      "card-gap": "12px",
                      "unit-xs": "4px",
                      "unit-lg": "24px",
                      "unit-sm": "8px",
                      "container-margin": "16px",
                      "unit-xl": "32px",
                      "unit-md": "16px"
              },
              "fontFamily": {
                      "label-md": ["Inter"],
                      "headline-lg": ["Inter"],
                      "status-badge": ["Inter"],
                      "body-md": ["Inter"],
                      "label-sm": ["Inter"],
                      "headline-md": ["Inter"],
                      "body-lg": ["Inter"]
              },
              "fontSize": {
                      "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                      "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                      "headline-lg-mobile": ["20px", {"lineHeight": "28px", "fontWeight": "700"}],
                      "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                      "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                      "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                      "label-sm-mobile": ["10px", {"lineHeight": "12px", "fontWeight": "500"}],
                      "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
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
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background text-on-surface font-body-md min-h-screen flex flex-col items-center">
<!-- Top App Bar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
<button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container active:scale-95 transition-all" onclick="window.location.href='/employee/dashboard'">
<span class="material-symbols-outlined text-primary">arrow_back</span>
</button>
<h1 class="text-headline-md font-headline-md font-bold text-primary">Payslip Detail</h1>
<!-- TODO Phase 4: connect action -->
<button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container active:scale-95 transition-all">
<span class="material-symbols-outlined text-primary">notifications</span>
</button>
</header>
<!-- Main Content Canvas -->
<main class="w-full max-w-[390px] pt-16 pb-24 px-container-margin space-y-6">
<!-- Employee Summary Info -->
<section class="mt-6 flex items-start gap-4 p-4 bg-white rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] border border-border">
<div class="relative">
<img class="w-16 h-16 rounded-full object-cover border-2 border-primary-container" data-alt="A professional studio headshot of Alex Rivers, a young male product designer with a friendly expression. He is wearing a minimalist dark polo shirt against a clean, soft-lit gray background. The lighting is crisp and even, reflecting a corporate and modern HR professional environment. High-quality photographic style with a shallow depth of field." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBcKZSAtGGZCVZVNnkxi7UPJpXp-wofOEpK27GjXPgt3Ca-P8tfumkQv8encEIDOluwe4yJBGfg8YL3ulGVmGsFpdqcaZ97QTrvxWm7vfIzjDRf18uM-ZIZsJoGM0rTvfoW4dhq_XDv5Y4CWVW7Zb_lUtXjlsUb3BXM0WmZM_jgeSZprWvjjwtKdCT7KQXQe6XFSZqMK606NFfEetYYi91f8A7JbEvp3Zw3GBpBD6GQCRGvl9jlZqaBhij9pFUrMFBzp7WOsOtVAWI"/>
<div class="absolute -bottom-1 -right-1 bg-success w-4 h-4 rounded-full border-2 border-white"></div>
</div>
<div class="flex-1">
<div class="flex justify-between items-start">
<div>
<h2 class="font-headline-md text-body-lg font-bold">Alex Rivers</h2>
<p class="text-on-surface-variant font-body-md">Product Designer</p>
</div>
<span class="bg-success/10 text-success px-3 py-1 rounded-full font-status-badge text-status-badge uppercase tracking-wider">Paid</span>
</div>
<div class="mt-2 flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined text-[16px]">apartment</span>
<p class="font-label-md text-label-md">Product Department</p>
</div>
</div>
</section>
<!-- Payroll Period Header -->
<div class="flex items-center justify-between px-2">
<h3 class="font-headline-md text-on-surface">June 2024</h3>
<span class="text-on-surface-variant font-label-md flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">calendar_today</span>
                Issued: Jun 28, 2024
            </span>
</div>
<!-- Net Salary Hero Card -->
<section class="bg-primary-container text-on-primary-container p-6 rounded-xl shadow-lg relative overflow-hidden">
<div class="relative z-10">
<p class="font-label-md text-label-md uppercase opacity-80 mb-1">Net Salary</p>
<h4 class="text-[32px] font-bold tracking-tight">$4,250.00</h4>
<div class="mt-4 flex items-center gap-2">
<span class="material-symbols-outlined text-[18px]">verified</span>
<p class="font-body-md text-on-primary-container/90">Bank Transfer Successful</p>
</div>
</div>
<!-- Decorative background element -->
<div class="absolute -right-4 -bottom-4 opacity-10">
<span class="material-symbols-outlined text-[120px]" style="font-variation-settings: 'FILL' 1;">payments</span>
</div>
</section>
<!-- Earnings Breakdown -->
<section class="space-y-3">
<div class="flex items-center justify-between px-2">
<h5 class="font-headline-md text-on-surface text-[16px]">Earnings</h5>
<span class="text-success font-label-md">+$4,250.00</span>
</div>
<div class="bg-white rounded-xl border border-border shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] overflow-hidden">
<div class="p-4 flex justify-between items-center border-b border-border/50">
<span class="text-on-surface-variant">Basic Salary</span>
<span class="font-bold text-on-surface">$3,500.00</span>
</div>
<div class="p-4 flex justify-between items-center border-b border-border/50">
<span class="text-on-surface-variant">Allowances</span>
<span class="font-bold text-on-surface">$500.00</span>
</div>
<div class="p-4 flex justify-between items-center border-b border-border/50">
<span class="text-on-surface-variant">Performance Bonus</span>
<span class="font-bold text-on-surface">$150.00</span>
</div>
<div class="p-4 flex justify-between items-center">
<span class="text-on-surface-variant">Overtime (8.5 hrs)</span>
<span class="font-bold text-on-surface">$100.00</span>
</div>
</div>
</section>
<!-- Deductions Breakdown -->
<section class="space-y-3">
<div class="flex items-center justify-between px-2">
<h5 class="font-headline-md text-on-surface text-[16px]">Deductions</h5>
<span class="text-danger font-label-md">-$0.00</span>
</div>
<div class="bg-white rounded-xl border border-border shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] overflow-hidden">
<div class="p-4 flex justify-between items-center border-b border-border/50">
<span class="text-on-surface-variant">Late Deduction</span>
<span class="font-bold text-on-surface">$0.00</span>
</div>
<div class="p-4 flex justify-between items-center border-b border-border/50">
<span class="text-on-surface-variant">Attendance Deduction</span>
<span class="font-bold text-on-surface">$0.00</span>
</div>
<div class="p-4 flex justify-between items-center">
<span class="text-on-surface-variant">Tax / Insurance</span>
<span class="font-bold text-on-surface">$0.00</span>
</div>
</div>
</section>
<!-- Summary Totals -->
<section class="p-5 bg-surface-container rounded-xl border-2 border-dashed border-primary/20 space-y-2">
<div class="flex justify-between items-center">
<span class="font-label-md text-on-surface-variant">Total Earnings</span>
<span class="font-bold text-on-surface">$4,250.00</span>
</div>
<div class="flex justify-between items-center">
<span class="font-label-md text-on-surface-variant">Total Deductions</span>
<span class="font-bold text-danger">($0.00)</span>
</div>
<hr class="border-outline-variant my-2"/>
<div class="flex justify-between items-center">
<span class="font-bold text-primary font-headline-md">Final Net Salary</span>
<span class="text-xl font-extrabold text-primary">$4,250.00</span>
</div>
</section>
<!-- Footer Actions -->
<section class="space-y-4 pt-4">
<!-- TODO Phase 4: connect action -->
<button class="w-full h-14 bg-white border-2 border-primary text-primary font-bold rounded-xl flex items-center justify-center gap-2 hover:bg-primary-container/10 active:scale-95 transition-all">
<span class="material-symbols-outlined">download</span>
                Download PDF Payslip
            </button>
<div class="flex gap-3 p-4 bg-surface-container-low rounded-xl border border-border">
<span class="material-symbols-outlined text-warning">info</span>
<p class="text-label-md text-on-surface-variant leading-relaxed">
                    PDF download is available after payroll is locked. If you notice any discrepancies, please contact the HR department before the 30th.
                </p>
</div>
</section>
</main>
<!-- Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 bg-surface border-t border-border backdrop-blur-md shadow-lg">
<!-- Home -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-colors" href="/employee/dashboard">
<span class="material-symbols-outlined">home</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Home</span>
</a>
<!-- Attendance -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-colors" href="/attendance/checkin">
<span class="material-symbols-outlined">schedule</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Attendance</span>
</a>
<!-- Leave -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-colors" href="/leave/history">
<span class="material-symbols-outlined">event_note</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Leave</span>
</a>
<!-- Payslip (Active) -->
<a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 transition-all active:scale-90 shadow-md" href="/payslip/detail">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">payments</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Payslip</span>
</a>
<!-- Profile -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-colors" href="/profile">
<span class="material-symbols-outlined">person</span>
<span class="font-label-sm text-label-sm-mobile mt-1">Profile</span>
</a>
</nav>
<script>
        // Micro-interactions
        document.querySelectorAll('button, a').forEach(el => {
            el.addEventListener('click', (e) => {
                // Prevent default if href is #
                if(el.getAttribute('href') === '#') e.preventDefault();
            });
        });

        // Simple scroll observer to adjust header shadow
        const header = document.querySelector('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                header.classList.add('shadow-md');
            } else {
                header.classList.remove('shadow-md');
            }
        });
    </script>
</body></html>
