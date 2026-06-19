<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>HRIS Mobile App - Login</title>
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
                    "surface-variant": "#dce2f3",
                    "surface-container-highest": "#dce2f3",
                    "on-tertiary-fixed": "#351000",
                    "secondary-fixed": "#e3dfff",
                    "surface": "#F9FAFB",
                    "outline-variant": "#c7c4d8",
                    "warning": "#F59E0B",
                    "inverse-primary": "#c3c0ff",
                    "surface-tint": "#4d44e3",
                    "surface-dim": "#d3daea",
                    "error": "#ba1a1a",
                    "error-container": "#ffdad6",
                    "on-primary-fixed": "#0f0069",
                    "inverse-on-surface": "#ebf1ff",
                    "surface-bright": "#f9f9ff",
                    "primary": "#3525cd",
                    "on-secondary-fixed": "#100069",
                    "on-primary": "#ffffff",
                    "on-primary-container": "#dad7ff",
                    "secondary": "#4e45d5",
                    "on-surface": "#151c27",
                    "danger": "#EF4444",
                    "tertiary-fixed-dim": "#ffb695",
                    "secondary-container": "#6860ef",
                    "on-surface-variant": "#464555",
                    "border": "#E5E7EB",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "success": "#10B981",
                    "surface-container-low": "#f0f3ff",
                    "surface-container-lowest": "#ffffff",
                    "on-secondary-container": "#fffbff",
                    "on-error": "#ffffff",
                    "on-secondary-fixed-variant": "#372abf",
                    "on-primary-fixed-variant": "#3323cc",
                    "background": "#f9f9ff",
                    "inverse-surface": "#2a313d",
                    "secondary-fixed-dim": "#c3c0ff",
                    "on-tertiary": "#ffffff",
                    "surface-container": "#e7eefe",
                    "primary-fixed": "#e2dfff",
                    "tertiary-fixed": "#ffdbcc",
                    "primary-fixed-dim": "#c3c0ff",
                    "on-secondary": "#ffffff",
                    "on-background": "#151c27",
                    "outline": "#777587",
                    "on-error-container": "#93000a",
                    "tertiary": "#7e3000",
                    "on-tertiary-container": "#ffd2be",
                    "tertiary-container": "#a44100",
                    "primary-container": "#4f46e5",
                    "surface-container-high": "#e2e8f8"
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
                    "unit-lg": "24px",
                    "unit-sm": "8px",
                    "unit-xl": "32px",
                    "card-gap": "12px",
                    "container-margin": "16px"
            },
            "fontFamily": {
                    "label-md": [
                            "Inter"
                    ],
                    "status-badge": [
                            "Inter"
                    ],
                    "headline-lg": [
                            "Inter"
                    ],
                    "body-lg": [
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
                    ]
            },
            "fontSize": {
                    "label-md": [
                            "12px",
                            {
                                    "lineHeight": "16px",
                                    "letterSpacing": "0.05em",
                                    "fontWeight": "600"
                            }
                    ],
                    "status-badge": [
                            "12px",
                            {
                                    "lineHeight": "12px",
                                    "fontWeight": "700"
                            }
                    ],
                    "headline-lg": [
                            "24px",
                            {
                                    "lineHeight": "32px",
                                    "fontWeight": "700"
                            }
                    ],
                    "body-lg": [
                            "16px",
                            {
                                    "lineHeight": "24px",
                                    "fontWeight": "400"
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
                    ]
            }
    },
        },
      }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB; /* surface */
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen text-on-surface antialiased bg-surface">
<div class="w-full max-w-[390px] h-screen md:h-[844px] md:rounded-[2rem] md:shadow-2xl bg-surface-container-lowest flex flex-col relative overflow-hidden ring-1 ring-border shadow-sm mx-auto my-0 md:my-8">
<!-- Header / Logo Area -->
<div class="flex flex-col items-center justify-center pt-24 pb-8 px-container-margin">
<div class="w-16 h-16 bg-primary-container rounded-2xl flex items-center justify-center shadow-sm mb-6">
<span class="material-symbols-outlined text-on-primary-container text-[32px]">
                    business_center
                </span>
</div>
<h1 class="font-headline-lg text-headline-lg text-on-surface text-center mb-2">HRIS Mobile App</h1>
<p class="font-body-md text-body-md text-on-surface-variant text-center">Sign in to your employee account</p>
</div>
<!-- Form Area -->
<div class="flex-1 px-container-margin pt-4 flex flex-col gap-6 w-full">
<form action="{{ route('login.attempt') }}" class="flex flex-col gap-unit-md w-full" method="POST">
@csrf
@if ($errors->any())
<div class="rounded-lg border border-error/20 bg-error-container/40 px-3 py-2 text-sm text-error">
{{ $errors->first() }}
</div>
@endif
<!-- Email Field -->
<div class="flex flex-col gap-unit-xs">
<label class="font-label-md text-label-md text-on-surface-variant" for="email">Email</label>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-outline text-[20px]">
                                mail
                            </span>
</div>
<input class="block w-full pl-10 pr-3 py-3 border border-border rounded-lg bg-surface focus:ring-primary focus:border-primary font-body-md text-body-md text-on-surface shadow-sm" id="email" name="email" placeholder="employee@company.com" required="" type="email" value="{{ old('email') }}">
</div>
</div>
<!-- Password Field -->
<div class="flex flex-col gap-unit-xs">
<label class="font-label-md text-label-md text-on-surface-variant" for="password">Password</label>
<div class="relative">
<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
<span class="material-symbols-outlined text-outline text-[20px]">
                                lock
                            </span>
</div>
<input class="block w-full pl-10 pr-10 py-3 border border-border rounded-lg bg-surface focus:ring-primary focus:border-primary font-body-md text-body-md text-on-surface shadow-sm" id="password" name="password" placeholder="••••••••" required="" type="password">
<button class="absolute inset-y-0 right-0 pr-3 flex items-center text-outline hover:text-on-surface transition-colors" onclick="togglePassword()" type="button">
<span class="material-symbols-outlined text-[20px]" id="password-toggle-icon">
                                visibility
                            </span>
</button>
</div>
<!-- Forgot Password Link -->
<div class="flex justify-end w-full mt-2">
<!-- TODO Phase 5: connect action -->
<a class="font-label-md text-label-md text-primary hover:text-primary-fixed-variant transition-colors" href="#">Forgot Password?</a>
</div>
</div>
<!-- Submit Button -->
<div class="pt-4">
<button class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-lg shadow-sm font-label-md text-label-md text-on-primary bg-primary-container hover:bg-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors active:scale-[0.98]" type="submit">
                        Login
                    </button>
</div>
</form>
</div>
<!-- Footer -->
<div class="pb-8 pt-4 px-container-margin w-full flex justify-center">
<p class="font-label-sm text-label-sm text-outline">© Company Name 2024</p>
</div>
</div>
<script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'visibility';
            }
        }
    </script>
</body></html>
