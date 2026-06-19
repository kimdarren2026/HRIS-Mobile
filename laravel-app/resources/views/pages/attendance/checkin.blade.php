<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<title>Attendance Check-In - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "secondary-fixed-dim": "#c3c0ff",
                    "on-secondary": "#ffffff",
                    "outline-variant": "#c7c4d8",
                    "surface-tint": "#4d44e3",
                    "on-tertiary-container": "#ffd2be",
                    "on-primary-fixed": "#0f0069",
                    "outline": "#777587",
                    "surface-container-low": "#f0f3ff",
                    "inverse-on-surface": "#ebf1ff",
                    "surface-container-highest": "#dce2f3",
                    "secondary-fixed": "#e3dfff",
                    "error-container": "#ffdad6",
                    "surface-bright": "#f9f9ff",
                    "primary-fixed": "#e2dfff",
                    "on-background": "#151c27",
                    "tertiary-container": "#a44100",
                    "secondary-container": "#6860ef",
                    "on-tertiary-fixed": "#351000",
                    "surface-dim": "#d3daea",
                    "primary": "#3525cd",
                    "on-tertiary": "#ffffff",
                    "on-secondary-fixed": "#100069",
                    "surface-container": "#e7eefe",
                    "tertiary-fixed-dim": "#ffb695",
                    "secondary": "#4e45d5",
                    "background": "#f9f9ff",
                    "surface-container-high": "#e2e8f8",
                    "on-primary-container": "#dad7ff",
                    "tertiary-fixed": "#ffdbcc",
                    "on-surface-variant": "#464555",
                    "on-surface": "#151c27",
                    "error": "#ba1a1a",
                    "surface-container-lowest": "#ffffff",
                    "on-primary": "#ffffff",
                    "danger": "#EF4444",
                    "on-secondary-fixed-variant": "#372abf",
                    "surface-variant": "#dce2f3",
                    "surface": "#F9FAFB",
                    "border": "#E5E7EB",
                    "inverse-primary": "#c3c0ff",
                    "tertiary": "#7e3000",
                    "on-primary-fixed-variant": "#3323cc",
                    "on-secondary-container": "#fffbff",
                    "primary-container": "#4f46e5",
                    "success": "#10B981",
                    "on-error-container": "#93000a",
                    "warning": "#F59E0B",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "on-error": "#ffffff",
                    "inverse-surface": "#2a313d",
                    "primary-fixed-dim": "#c3c0ff"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "card-gap": "12px",
                    "container-margin": "16px",
                    "unit-lg": "24px",
                    "unit-xs": "4px",
                    "unit-md": "16px",
                    "unit-xl": "32px",
                    "unit-sm": "8px"
            },
            "fontFamily": {
                    "body-lg": [
                            "Inter"
                    ],
                    "status-badge": [
                            "Inter"
                    ],
                    "headline-md": [
                            "Inter"
                    ],
                    "headline-lg": [
                            "Inter"
                    ],
                    "label-sm": [
                            "Inter"
                    ],
                    "label-md": [
                            "Inter"
                    ],
                    "body-md": [
                            "Inter"
                    ]
            },
            "fontSize": {
                    "body-lg": [
                            "16px",
                            {
                                    "lineHeight": "24px",
                                    "fontWeight": "400"
                            }
                    ],
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
                    "headline-lg": [
                            "24px",
                            {
                                    "lineHeight": "32px",
                                    "fontWeight": "700"
                            }
                    ],
                    "label-sm": [
                            "11px",
                            {
                                    "lineHeight": "14px",
                                    "fontWeight": "500"
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
                    "body-md": [
                            "14px",
                            {
                                    "lineHeight": "20px",
                                    "fontWeight": "400"
                            }
                    ]
            }
        },
        },
      }
    </script>
<style>
        body {
            -webkit-tap-highlight-color: transparent;
        }
        /* Pulse animation for map pin */
        @keyframes pin-pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }
        .animate-pin-pulse {
            animation: pin-pulse 2s infinite ease-in-out;
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
</head>
<body class="bg-background text-on-surface font-body-md min-h-screen flex flex-col antialiased w-full max-w-[390px] mx-auto overflow-x-hidden pb-24">
<!-- TopAppBar (Suppressed as per rules for transactional screen, but replacing with simplified back header as requested) -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
<button aria-label="Go back" class="p-2 -ml-2 rounded-full hover:bg-surface-container active:scale-95 transition-all text-on-surface-variant focus:outline-none" onclick="window.location.href='/employee/dashboard'">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">arrow_back</span>
</button>
<h1 class="font-headline-md text-headline-md text-primary ml-2 flex-1">Check In</h1>
</header>
<!-- Main Content Canvas -->
<main class="flex-1 mt-16 px-container-margin py-unit-md flex flex-col gap-unit-lg">
<!-- Time Context -->
<section class="flex flex-col items-center justify-center pt-unit-sm">
<p class="font-body-md text-body-md text-on-surface-variant mb-1" id="current-date">Thursday, Oct 26</p>
<div class="flex items-baseline gap-1">
<span class="font-headline-lg text-4xl font-bold tracking-tight text-on-surface" id="current-time">08:45</span>
<span class="font-label-md text-label-md text-on-surface-variant font-bold">AM</span>
</div>
</section>
<!-- Location Card -->
<section class="bg-surface rounded-xl border border-border shadow-sm overflow-hidden flex flex-col">
<div class="p-4 border-b border-border flex justify-between items-center bg-surface-container-low">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">location_on</span>
<h2 class="font-label-md text-label-md text-on-surface">Your Location</h2>
</div>
<!-- Status Badge -->
<div class="flex items-center gap-1.5 px-2 py-1 rounded-full bg-success/10 border border-success/20"><div class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></div><span class="font-status-badge text-status-badge text-success">Within office radius</span></div>
</div>
<!-- Map Preview Area -->
<div class="relative w-full h-32 bg-surface-container overflow-hidden">
<div class="absolute inset-0 bg-cover bg-center opacity-80 mix-blend-multiply" data-alt="A clean, modern top-down satellite map view of a corporate office park area, showing streets and buildings in a light gray and blue UI color palette, resembling a modern digital mapping application. The lighting is bright and clear." style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCQ2p0LO_StLCobSmCIwR5iYZpSZDmOUUNNveknET2pp6_bLdcuCyB6L4d48eOUGBlqNM8clsbaLcsKsLKRsUbpjKOlmcf1A9XBptEOhhMCOeI43vRj4jROALHhdUCLsMXKfqQuc1vWes3a0rQyHRaoQWv4JZlJZ-0_7U5Av-G4IG3jInocLMTFrUbqCcCxSXr0PAIefMI6F7INpqalm1JfVFzYJR6c7Vt2v61eD3OnlAH__s6saZdr4whrPzsERDhdkC0IWJNR9qQ')"></div>
<!-- Center Pin -->
<div class="absolute inset-0 flex items-center justify-center">
<div class="relative flex items-center justify-center">
<div class="absolute w-12 h-12 rounded-full bg-primary/20 animate-pin-pulse"></div>
<div class="absolute w-4 h-4 rounded-full bg-primary shadow-sm border-2 border-white z-10"></div>
<!-- Placeholder Pin Icon for clarity -->
<span class="material-symbols-outlined absolute -top-8 text-primary drop-shadow-md" style="font-variation-settings: 'FILL' 1; font-size: 32px;">location_on</span>
</div>
</div>
</div>
<!-- GPS Details Footer -->
<div class="p-3 bg-surface text-center">
<p class="font-label-sm text-label-sm text-on-surface-variant flex items-center justify-center gap-1">
<span class="material-symbols-outlined text-[14px]">my_location</span>
                    Accuracy: 5m <span class="mx-1">•</span> HQ Building, Sector 4
                </p>
<p class="font-label-sm text-label-sm text-on-surface-variant mt-2 opacity-80 italic">Note: Reason field only required when outside office radius.</p></div>
</section>
<!-- Selfie Capture Section -->
<section class="flex flex-col gap-unit-xs">
<label class="font-label-md text-label-md text-on-surface px-1">Photo Verification</label>
<div class="relative w-full h-48 bg-surface-container-high rounded-xl border-2 border-dashed border-outline-variant overflow-hidden group active:border-primary transition-colors cursor-pointer flex flex-col items-center justify-center">
<!-- Placeholder for actual camera feed -->
<div class="absolute inset-0 bg-surface-dim z-0"></div>
<div class="z-10 flex flex-col items-center gap-2 text-on-surface-variant group-hover:text-primary transition-colors">
<div class="w-14 h-14 rounded-full bg-surface shadow-sm flex items-center justify-center">
<span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0;">photo_camera</span>
</div>
<span class="font-label-sm text-label-sm font-medium">Tap to take selfie</span>
</div>
</div>
</section>
<!-- Conditional Reason Input (Mocked as visible for design requirement) -->
<section class="flex flex-col gap-unit-xs hidden">
<label class="font-label-md text-label-md text-on-surface px-1 flex justify-between" for="reason">
                Reason for checking in outside radius
                <span class="text-danger">*</span>
</label>
<textarea class="w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow resize-none placeholder:text-outline" id="reason" placeholder="E.g., Client meeting, working remotely..." rows="2"></textarea>
</section>
</main>
<!-- Bottom Action Area (Fixed) -->
<div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] px-container-margin pb-unit-md pt-unit-sm bg-surface/90 backdrop-blur-md border-t border-border z-40">
<button class="w-full bg-primary hover:bg-primary/90 text-on-primary font-headline-md text-body-lg font-semibold py-3.5 rounded-xl shadow-sm active:scale-[0.98] transition-all flex items-center justify-center gap-2" onclick="window.location.href='/attendance/history'">
<span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">login</span>
            Confirm Check In
        </button>
</div>
<script>
        // Simple script to update clock
        function updateClock() {
            const now = new Date();
            const timeElement = document.getElementById('current-time');
            const dateElement = document.getElementById('current-date');
            
            if(timeElement) {
                let hours = now.getHours();
                let minutes = now.getMinutes();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; 
                minutes = minutes < 10 ? '0' + minutes : minutes;
                
                // Assuming we want to keep the AM/PM span separate based on the HTML structure
                timeElement.innerText = hours + ':' + minutes;
                // Update AM/PM span which is the next sibling
                const ampmSpan = timeElement.nextElementSibling;
                if(ampmSpan) ampmSpan.innerText = ampm;
            }

            if(dateElement) {
                const options = { weekday: 'long', month: 'short', day: 'numeric' };
                dateElement.innerText = now.toLocaleDateString('en-US', options);
            }
        }
        
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body></html>
