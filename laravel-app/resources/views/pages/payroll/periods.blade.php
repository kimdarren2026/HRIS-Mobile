<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Payroll Periods - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "secondary-fixed": "#e3dfff",
                  "surface-container-low": "#f0f3ff",
                  "on-tertiary-fixed": "#351000",
                  "inverse-surface": "#2a313d",
                  "surface-variant": "#dce2f3",
                  "surface": "#F9FAFB",
                  "on-tertiary-fixed-variant": "#7b2f00",
                  "surface-container-high": "#e2e8f8",
                  "surface-bright": "#f9f9ff",
                  "on-secondary-fixed-variant": "#372abf",
                  "surface-container-lowest": "#ffffff",
                  "on-surface": "#151c27",
                  "surface-container": "#e7eefe",
                  "warning": "#F59E0B",
                  "on-secondary": "#ffffff",
                  "on-primary-fixed": "#0f0069",
                  "secondary-fixed-dim": "#c3c0ff",
                  "background": "#f9f9ff",
                  "on-tertiary": "#ffffff",
                  "primary-fixed": "#e2dfff",
                  "inverse-on-surface": "#ebf1ff",
                  "on-secondary-container": "#fffbff",
                  "on-surface-variant": "#464555",
                  "danger": "#EF4444",
                  "primary-container": "#4f46e5",
                  "inverse-primary": "#c3c0ff",
                  "surface-container-highest": "#dce2f3",
                  "surface-tint": "#4d44e3",
                  "on-primary-container": "#dad7ff",
                  "tertiary-fixed": "#ffdbcc",
                  "on-primary-fixed-variant": "#3323cc",
                  "tertiary": "#7e3000",
                  "tertiary-container": "#a44100",
                  "surface-dim": "#d3daea",
                  "outline": "#777587",
                  "error": "#ba1a1a",
                  "on-background": "#151c27",
                  "on-primary": "#ffffff",
                  "primary": "#3525cd",
                  "on-secondary-fixed": "#100069",
                  "on-error": "#ffffff",
                  "success": "#10B981",
                  "on-tertiary-container": "#ffd2be",
                  "tertiary-fixed-dim": "#ffb695",
                  "border": "#E5E7EB",
                  "primary-fixed-dim": "#c3c0ff",
                  "error-container": "#ffdad6",
                  "on-error-container": "#93000a",
                  "secondary-container": "#6860ef",
                  "secondary": "#4e45d5",
                  "outline-variant": "#c7c4d8"
          },
          "borderRadius": {
                  "DEFAULT": "0.25rem",
                  "lg": "0.5rem",
                  "xl": "0.75rem",
                  "full": "9999px"
          },
          "spacing": {
                  "unit-xs": "4px",
                  "card-gap": "12px",
                  "unit-md": "16px",
                  "container-margin": "16px",
                  "unit-xl": "32px",
                  "unit-sm": "8px",
                  "unit-lg": "24px"
          },
          "fontFamily": {
                  "label-md": ["Inter"],
                  "label-sm": ["Inter"],
                  "headline-md": ["Inter"],
                  "body-lg": ["Inter"],
                  "status-badge": ["Inter"],
                  "headline-lg": ["Inter"],
                  "body-md": ["Inter"]
          },
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
    body {
      font-family: 'Inter', sans-serif;
      -webkit-tap-highlight-color: transparent;
    }
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .custom-scrollbar::-webkit-scrollbar {
      display: none;
    }
    .safe-bottom {
      padding-bottom: env(safe-area-inset-bottom);
    }
  </style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-[390px] mx-auto min-h-screen relative shadow-2xl">
<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex justify-between items-center px-container-margin">
<div class="flex items-center gap-3">
<button class="transition-colors duration-200 active:opacity-70 text-primary p-1">
<span class="material-symbols-outlined">menu</span>
</button>
<h1 class="font-headline-md text-headline-md font-bold text-primary">Payroll Periods</h1>
</div>
<button class="transition-colors duration-200 active:opacity-70 text-primary p-1 relative">
<span class="material-symbols-outlined">notifications</span>
<span class="absolute top-1 right-1 w-2 h-2 bg-danger rounded-full border border-surface"></span>
</button>
</header>
<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">
<!-- Top Summary Section -->
<section class="grid grid-cols-2 gap-unit-sm">
<div class="col-span-2 bg-white p-4 rounded-xl border border-border shadow-sm flex flex-col justify-between h-28 relative overflow-hidden">
<div class="z-10">
<p class="font-label-md text-label-md text-on-surface-variant mb-1">Active Period</p>
<h2 class="font-headline-lg text-headline-lg text-primary">June 2026</h2>
</div>
<div class="z-10 flex items-center text-success font-label-sm text-label-sm gap-1">
<span class="material-symbols-outlined text-[14px]">calendar_today</span>
<span class="">Current running</span>
</div>
<div class="absolute -right-4 -bottom-4 text-surface-container-high scale-150">
<span class="material-symbols-outlined text-[96px] opacity-10">receipt_long</span>
</div>
</div>
<div class="bg-white p-4 rounded-xl border border-border shadow-sm flex flex-col justify-between h-24">
<p class="font-label-md text-label-md text-on-surface-variant">Employees</p>
<div class="flex items-end justify-between">
<span class="font-headline-md text-headline-md text-on-surface">125</span>
<span class="material-symbols-outlined text-primary opacity-40">groups</span>
</div>
</div>
<div class="bg-white p-4 rounded-xl border border-border shadow-sm flex flex-col justify-between h-24">
<p class="font-label-md text-label-md text-on-surface-variant">Pending Actions</p>
<div class="flex items-end justify-between">
<span class="font-headline-md text-headline-md text-warning">3</span>
<span class="material-symbols-outlined text-warning opacity-40">pending_actions</span>
</div>
</div>
</section>
<!-- Action Buttons -->
<section class="flex flex-col gap-unit-sm">
<button class="w-full bg-primary-container text-on-primary py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:scale-[0.98] transition-transform">
<span class="material-symbols-outlined">add_circle</span>
        Create New Period
      </button>
<button class="w-full border border-primary text-primary py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:scale-[0.98] transition-transform bg-white">
<span class="material-symbols-outlined">download</span>
        Export History
      </button>
</section>
<!-- Search & Filters -->
<section class="flex flex-col gap-unit-sm">
<div class="relative">
<span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant">search</span>
<input class="w-full bg-white border border-border rounded-xl pl-10 pr-4 py-3 text-body-md font-body-md focus:ring-1 focus:ring-primary outline-none" placeholder="Search period name or date range" type="text">
</div>
<div class="flex gap-unit-sm">
<div class="flex-1 relative">
<select class="w-full bg-white border border-border rounded-xl px-4 py-2.5 text-label-md font-label-md appearance-none"><option>All Status</option><option>Draft</option><option>Calculated</option><option>HR Review</option><option>Finance Approval</option><option>Locked</option><option>Paid</option></select>
<span class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant pointer-events-none">expand_more</span>
</div>
<div class="flex-1 relative">
<select class="w-full bg-white border border-border rounded-xl px-4 py-2.5 text-label-md font-label-md appearance-none">
<option>Year: 2026</option>
<option>Year: 2025</option>
</select>
<span class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant pointer-events-none">expand_more</span>
</div>
</div>
</section>
<!-- Payroll Period List -->
<section class="flex flex-col gap-unit-md">
<!-- Card 1: June 2026 -->
<div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-4">
<div class="flex justify-between items-start">
<div>
<h3 class="font-headline-md text-headline-md text-on-surface mb-1">June 2026 Payroll</h3>
<p class="font-body-md text-body-md text-on-surface-variant">Jun 01 - Jun 30, 2026</p>
</div>
<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-status-badge text-status-badge">Calculated</span>
</div>
<div class="flex items-center gap-4 text-on-surface-variant">
<div class="flex items-center gap-1.5 font-label-md text-label-md">
<span class="material-symbols-outlined text-[18px]">badge</span>
            125 Employees
          </div>
</div><div class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant"><span class="material-symbols-outlined text-[18px]">payments</span>Total Net Salary: $685,400</div>
<div class="grid grid-cols-3 gap-2 pt-2 border-t border-border">
<button class="bg-surface-container-low text-primary py-2 rounded-lg font-label-sm text-label-sm active:bg-surface-variant">View</button>
<button class="bg-surface-container-low text-primary py-2 rounded-lg font-label-sm text-label-sm active:bg-surface-variant">Process</button>
<button class="bg-primary text-white py-2 rounded-lg font-label-sm text-label-sm active:opacity-90">Approve</button>
</div>
</div>
<!-- Card 2: May 2026 -->
<div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-4">
<div class="flex justify-between items-start">
<div>
<h3 class="font-headline-md text-headline-md text-on-surface mb-1">May 2026 Payroll</h3>
<p class="font-body-md text-body-md text-on-surface-variant">May 01 - May 31, 2026</p>
</div>
<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-status-badge text-status-badge">Paid</span>
</div>
<div class="flex items-center gap-4 text-on-surface-variant">
<div class="flex items-center gap-1.5 font-label-md text-label-md">
<span class="material-symbols-outlined text-[18px]">badge</span>
            120 Employees
          </div>
</div><div class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant"><span class="material-symbols-outlined text-[18px]">payments</span>Total Net Salary: $658,200</div>
<div class="pt-2 border-t border-border">
<button class="w-full bg-surface-container-low text-primary py-2 rounded-lg font-label-sm text-label-sm active:bg-surface-variant">View Report</button>
</div>
</div>
<!-- Card 3: April 2026 -->
<div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-4">
<div class="flex justify-between items-start">
<div>
<h3 class="font-headline-md text-headline-md text-on-surface mb-1">April 2026 Payroll</h3>
<p class="font-body-md text-body-md text-on-surface-variant">Apr 01 - Apr 30, 2026</p>
</div>
<span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-status-badge text-status-badge">Locked</span>
</div>
<div class="flex items-center gap-4 text-on-surface-variant">
<div class="flex items-center gap-1.5 font-label-md text-label-md">
<span class="material-symbols-outlined text-[18px]">badge</span>
            118 Employees
          </div>
</div><div class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant"><span class="material-symbols-outlined text-[18px]">payments</span>Total Net Salary: $645,100</div>
<div class="pt-2 border-t border-border">
<button class="w-full bg-surface-container-low text-primary py-2 rounded-lg font-label-sm text-label-sm active:bg-surface-variant">View</button>
</div>
</div>
<!-- Card 4: July 2026 -->
<div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-4">
<div class="flex justify-between items-start">
<div>
<h3 class="font-headline-md text-headline-md text-on-surface mb-1">July 2026 Payroll</h3>
<p class="font-body-md text-body-md text-on-surface-variant">Jul 01 - Jul 31, 2026</p>
</div>
<span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-status-badge text-status-badge">Draft</span>
</div>
<div class="flex items-center gap-4 text-on-surface-variant">
<div class="flex items-center gap-1.5 font-label-md text-label-md">
<span class="material-symbols-outlined text-[18px]">badge</span>
            0 Employees
          </div>
</div><div class="flex items-center gap-1.5 font-label-md text-label-md text-on-surface-variant"><span class="material-symbols-outlined text-[18px]">payments</span>Total Net Salary: $0</div>
<div class="grid grid-cols-2 gap-2 pt-2 border-t border-border">
<button class="bg-surface-container-low text-primary py-2 rounded-lg font-label-sm text-label-sm active:bg-surface-variant">View</button>
<button class="bg-primary text-white py-2 rounded-lg font-label-sm text-label-sm active:opacity-90">Process</button>
</div>
</div>
</section>
</main>
<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
<a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="#">
<span class="material-symbols-outlined">home</span>
<span class="font-label-sm text-label-sm">Home</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="#">
<span class="material-symbols-outlined">badge</span>
<span class="font-label-sm text-label-sm">Employees</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="#">
<span class="material-symbols-outlined">fact_check</span>
<span class="font-label-sm text-label-sm">Approvals</span>
</a>
<a class="flex flex-col items-center justify-center text-primary bg-secondary-fixed rounded-xl px-3 py-1 transition-transform active:scale-95 duration-150" href="#">
<span class="material-symbols-outlined">analytics</span>
<span class="font-label-sm text-label-sm">Reports</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="#">
<span class="material-symbols-outlined">person</span>
<span class="font-label-sm text-label-sm">Profile</span>
</a>
</nav>
<script>
    // Subtle interaction script
    document.querySelectorAll('button, a').forEach(el => {
      el.addEventListener('click', (e) => {
        // Mock navigation or action response
        if(el.innerText.includes('Create')) {
          console.log('Action: Create New Period Modal');
        }
      });
    });
  </script>


</body></html>
