<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title>HR Approval Queue - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "outline": "#777587",
                    "on-error": "#ffffff",
                    "on-primary-fixed-variant": "#3323cc",
                    "on-tertiary-container": "#ffd2be",
                    "warning": "#F59E0B",
                    "background": "#f9f9ff",
                    "on-surface": "#151c27",
                    "on-primary-container": "#dad7ff",
                    "tertiary-fixed": "#ffdbcc",
                    "on-error-container": "#93000a",
                    "surface": "#F9FAFB",
                    "secondary-fixed": "#e3dfff",
                    "primary-fixed": "#e2dfff",
                    "surface-container-high": "#e2e8f8",
                    "success": "#10B981",
                    "surface-container": "#e7eefe",
                    "inverse-surface": "#2a313d",
                    "error-container": "#ffdad6",
                    "surface-container-low": "#f0f3ff",
                    "danger": "#EF4444",
                    "error": "#ba1a1a",
                    "on-primary-fixed": "#0f0069",
                    "surface-container-variant": "#f1f5f9",
                    "tertiary": "#7e3000",
                    "primary-fixed-dim": "#c3c0ff",
                    "on-secondary-fixed": "#100069",
                    "on-surface-variant": "#464555",
                    "surface-tint": "#4d44e3",
                    "secondary-container": "#6860ef",
                    "inverse-on-surface": "#ebf1ff",
                    "secondary-fixed-dim": "#c3c0ff",
                    "outline-variant": "#c7c4d8",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "on-background": "#151c27",
                    "tertiary-container": "#a44100",
                    "primary-container": "#4f46e5",
                    "surface-dim": "#d3daea",
                    "primary": "#3525cd",
                    "on-tertiary-fixed": "#351000",
                    "on-secondary": "#ffffff",
                    "on-secondary-fixed-variant": "#372abf",
                    "on-secondary-container": "#fffbff",
                    "on-primary": "#ffffff",
                    "surface-variant": "#dce2f3",
                    "on-tertiary": "#ffffff",
                    "tertiary-fixed-dim": "#ffb695",
                    "inverse-primary": "#c3c0ff",
                    "border": "#E5E7EB",
                    "surface-bright": "#f9f9ff",
                    "surface-container-highest": "#dce2f3",
                    "secondary": "#4e45d5"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "unit-sm": "8px",
                    "unit-lg": "24px",
                    "unit-md": "16px",
                    "unit-xl": "32px",
                    "unit-xs": "4px",
                    "container-margin": "16px",
                    "card-gap": "12px"
            },
            "fontFamily": {
                    "status-badge": ["Inter"],
                    "headline-lg": ["Inter"],
                    "body-lg": ["Inter"],
                    "body-md": ["Inter"],
                    "label-sm": ["Inter"],
                    "label-md": ["Inter"],
                    "headline-md": ["Inter"]
            },
            "fontSize": {
                    "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                    "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                    "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                    "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}]
            }
          },
        },
      }
    </script>
<style>
        body { font-family: 'Inter', sans-serif; background-color: #f9f9ff; -webkit-tap-highlight-color: transparent; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .active-tab-indicator { position: absolute; bottom: 0; left: 0; height: 3px; background-color: #3525cd; border-radius: 3px 3px 0 0; transition: all 0.3s ease; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
<style>
        body {
          min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="text-on-background min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-[72px]">
<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm h-16 flex justify-between items-center px-container-margin">
<div class="flex items-center gap-3">
<button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors active:scale-95 duration-150">
<span class="material-symbols-outlined text-on-surface-variant">menu</span>
</button>
<h1 class="font-headline-md text-headline-md font-bold text-primary">HR Approval Queue</h1>
</div>
<button class="relative w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors active:scale-95 duration-150">
<span class="material-symbols-outlined text-on-surface-variant">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-danger rounded-full"></span>
</button>
</header>
<main class="pt-16 max-w-[390px] mx-auto min-h-screen">
<!-- Tabs Section -->
<div class="bg-surface sticky top-16 z-40">
<div class="flex border-b border-outline-variant">
<!-- Attendance Tab (Active) -->
<button class="relative flex-1 flex items-center justify-center py-4 text-primary font-bold transition-colors">
<div class="flex items-center gap-2">
<span class="font-label-md text-label-md">Attendance</span>
<span class="bg-primary-container text-on-primary-container px-2 py-0.5 rounded-full text-[10px] font-bold">3</span>
</div>
<div class="active-tab-indicator w-full"></div>
</button>
<!-- Leave Tab (Inactive) -->
<button class="relative flex-1 flex items-center justify-center py-4 text-on-surface-variant hover:bg-surface-container-low transition-colors">
<div class="flex items-center gap-2">
<span class="font-label-md text-label-md">Leave</span>
<span class="bg-surface-container-highest text-on-surface-variant px-2 py-0.5 rounded-full text-[10px] font-bold">5</span>
</div>
</button>
</div>
</div>
<!-- Approval List Content -->
<div class="p-container-margin flex flex-col gap-unit-lg">
<!-- Attendance Card 1 -->
<div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col gap-unit-md p-unit-md">
<div class="flex items-start justify-between">
<div class="flex gap-3">
<img class="w-12 h-12 rounded-full object-cover" data-alt="Professional studio portrait of a young male professional named Alex Rivers with a friendly expression, wearing a modern business casual outfit. The lighting is soft and corporate, set against a clean, light-blue gradient background that aligns with a high-end SaaS HR application aesthetic." src="https://lh3.googleusercontent.com/aida-public/AB6AXuBFZucuwSCiIpIQ8NTu3ufVaO9HDfpBg9iK1quu3yd12T94WzAcKNDJEZjC6-XddiosLeS7V08IT1_5Jsh3pqAHsBw6RvF9q6tiI_IXhsKeX16_jLXKqgAqWXDziJ7AguUv_np5VXdyZ0qJZCIxAwxZiJ4yR4kxv5jiszkTuSiC1t5Ow4oVxh7sA-1EXCHnZ6u7zLwURfsCjGgU8o_stE3Rsx_Bww4Hm3l8F4UNxZ2fQuWSsLZZyJ4xjOlaJwut00VVWMs3DMBPisk"/>
<div>
<h3 class="font-headline-md text-[16px] font-bold text-on-surface">Alex Rivers</h3>
<p class="font-body-md text-on-surface-variant">Product • 2 hours ago</p>
</div>
</div>
<div class="bg-warning/10 text-warning px-3 py-1 rounded-full font-status-badge text-status-badge">
                    Pending Review
                </div>
</div>
<div class="bg-surface-container-low p-3 rounded-lg border border-outline-variant/30">
<div class="flex items-center gap-2 mb-1">
<span class="material-symbols-outlined text-primary text-[18px]">location_on</span>
<span class="font-label-md text-label-md text-on-surface-variant">Check-in outside radius - 350m</span>
</div>
<p class="font-body-md text-on-surface-variant mt-2 italic leading-relaxed">
                    "Site visit at client office in downtown."
                </p>
</div>
<div class="flex gap-3 pt-2">
<button class="flex-1 bg-success text-on-primary py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">check_circle</span>
                    Approve
                </button>
<button class="flex-1 border-2 border-danger text-danger py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">block</span>
                    Reject
                    <span class="material-symbols-outlined text-[14px]">edit_note</span>
</button>
</div>
</div>
<!-- Attendance Card 2 -->
<div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col gap-unit-md p-unit-md">
<div class="flex items-start justify-between">
<div class="flex gap-3">
<img class="w-12 h-12 rounded-full object-cover" data-alt="A professional headshot of a female employee named Sarah Jenkins with long brown hair, wearing a professional blazer. She has a confident, warm smile, photographed in a modern office environment with soft bokeh background. The overall aesthetic is professional, clean, and reliable, using soft natural light." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDCpfvrkH6sas_8gGJghK2lRRpD_sDMDvvQEzfG_G6Syo-G6DOVBQhS9jPzdM0ATv_shPeh6jAOfzGYbxrMTVP-jI2rrydilmOyYwRvwawGPRBXiIdseE_GKBwStrjEYUfEcffYeo3Hoc0viDG0pvXID-R_TrHoMaJD_epwsj4emIffYwtvvDpocVAVMQ8dsveYG7zEP1DnwtnzJEzghfZXqOmLVC7np-2U6Cn0ViUpwIkA_oVPkJJO6VAguH-kSTuUxCdWYpSAMQ0"/>
<div>
<h3 class="font-headline-md text-[16px] font-bold text-on-surface">Sarah Jenkins</h3>
<p class="font-body-md text-on-surface-variant">Marketing • 4 hours ago</p>
</div>
</div>
<div class="bg-warning/10 text-warning px-3 py-1 rounded-full font-status-badge text-status-badge">
                    Pending Review
                </div>
</div>
<div class="bg-surface-container-low p-3 rounded-lg border border-outline-variant/30">
<div class="flex items-center gap-2 mb-1">
<span class="material-symbols-outlined text-primary text-[18px]">distance</span>
<span class="font-label-md text-label-md text-on-surface-variant">Check-in outside radius - 1.2km</span>
</div>
<p class="font-body-md text-on-surface-variant mt-2 italic leading-relaxed">
                    "Forgot to check in at the entrance, checked in from my desk."
                </p>
</div>
<div class="flex gap-3 pt-2">
<button class="flex-1 bg-success text-on-primary py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">check_circle</span>
                    Approve
                </button>
<button class="flex-1 border-2 border-danger text-danger py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">block</span>
                    Reject
                    <span class="material-symbols-outlined text-[14px]">edit_note</span>
</button>
</div>
</div>
<!-- List Placeholder / Empty State hint -->
<div class="flex flex-col items-center justify-center py-unit-xl opacity-20 select-none">
<span class="material-symbols-outlined text-[64px]">rule</span>
<p class="font-label-md mt-2">End of queue</p>
</div>
</div>
</main>
<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-outline-variant shadow-sm h-[72px] flex justify-around items-center px-unit-sm pb-safe">
<!-- Home -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
<span class="material-symbols-outlined">home</span>
<span class="font-label-md text-[10px] mt-1">Home</span>
</button>
<!-- Employees -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
<span class="material-symbols-outlined">groups</span>
<span class="font-label-md text-[10px] mt-1">Employees</span>
</button>
<!-- Approvals (Active) -->
<button class="flex flex-col items-center justify-center bg-secondary-container text-on-secondary-container rounded-xl px-4 py-1.5 active:scale-90 duration-200">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">rule</span>
<span class="font-label-md text-[10px] mt-1">Approvals</span>
</button>
<!-- Reports -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
<span class="material-symbols-outlined">assessment</span>
<span class="font-label-md text-[10px] mt-1">Reports</span>
</button>
<!-- Profile -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200">
<span class="material-symbols-outlined">person</span>
<span class="font-label-md text-[10px] mt-1">Profile</span>
</button>
</nav>
<script>
    // Simple interactive micro-interactions for buttons
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('touchstart', function() {
            this.classList.add('opacity-80');
        });
        button.addEventListener('touchend', function() {
            this.classList.remove('opacity-80');
        });
    });
    
    // Tab switching logic (visual only for this prototype)
    const tabs = document.querySelectorAll('.flex.border-b button');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => {
                t.classList.remove('text-primary', 'font-bold');
                t.classList.add('text-on-surface-variant');
                const indicator = t.querySelector('.active-tab-indicator');
                if(indicator) indicator.remove();
            });
            tab.classList.add('text-primary', 'font-bold');
            tab.classList.remove('text-on-surface-variant');
            const indicator = document.createElement('div');
            indicator.className = 'active-tab-indicator w-full';
            tab.appendChild(indicator);
        });
    });
</script>
</body></html>
