<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Notification - HRIS Mobile App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "surface-variant": "#dce2f3", "surface-bright": "#f9f9ff",
                    "success": "#10B981", "on-error": "#ffffff",
                    "on-secondary": "#ffffff", "warning": "#F59E0B",
                    "on-surface": "#151c27", "surface-dim": "#d3daea",
                    "surface": "#F9FAFB", "on-primary": "#ffffff",
                    "primary-container": "#4f46e5", "primary": "#3525cd",
                    "error": "#ba1a1a", "danger": "#EF4444",
                    "on-surface-variant": "#464555", "surface-container-high": "#e2e8f8",
                    "outline-variant": "#c7c4d8", "outline": "#777587",
                    "background": "#f9f9ff", "on-background": "#151c27",
                    "border": "#E5E7EB", "surface-container": "#e7eefe",
                    "surface-container-low": "#f0f3ff",
                    "on-primary-container": "#dad7ff",
                },
                "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                "spacing": {
                    "unit-xs": "4px", "unit-md": "16px", "container-margin": "16px",
                    "unit-xl": "32px", "card-gap": "12px", "unit-sm": "8px", "unit-lg": "24px"
                },
                "fontFamily": { "headline-md": ["Inter"], "body-md": ["Inter"], "label-md": ["Inter"], "label-sm": ["Inter"], "body-lg": ["Inter"] },
                "fontSize": {
                    "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                    "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                    "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                    "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                    "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "500" }]
                }
            }
        }
    }
</script>
<style>
    body { min-height: max(884px, 100dvh); }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0; }
</style>
</head>
<body class="bg-surface font-body-md text-on-surface antialiased min-h-screen flex flex-col mx-auto max-w-[390px] relative">

<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
    <a href="{{ route('notifications.index') }}"
       class="text-on-surface-variant hover:bg-surface-container active:scale-95 transition-transform duration-150 p-2 rounded-full flex items-center justify-center">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="font-headline-md text-headline-md font-bold text-primary truncate px-2">Notification</h1>
    <form method="POST" action="{{ route('notifications.read', $notification) }}">
        @csrf @method('PATCH')
        <button type="submit"
                class="text-primary font-label-md text-label-md hover:bg-surface-container-low active:scale-95 transition-transform duration-150 px-3 py-2 rounded-lg {{ $notification->is_read ? 'opacity-30 cursor-default' : '' }}"
                {{ $notification->is_read ? 'disabled' : '' }}>
            Mark read
        </button>
    </form>
</header>

<main class="flex-grow pt-[72px] px-container-margin pb-unit-xl">

    <div class="mt-unit-md bg-white border border-border rounded-xl p-unit-md shadow-sm">
        @php
            $typeIcon = match($notification->type) {
                'attendance' => 'fingerprint',
                'leave'      => 'event_busy',
                'payroll'    => 'payments',
                'expense'    => 'receipt_long',
                default      => 'notifications',
            };
            $typeColor = match($notification->type) {
                'attendance' => 'text-primary bg-surface-container',
                'leave'      => 'text-warning bg-warning/10',
                'payroll'    => 'text-success bg-success/10',
                'expense'    => 'text-on-surface-variant bg-surface-container',
                default      => 'text-on-surface-variant bg-surface-container',
            };
        @endphp
        <div class="flex items-center gap-unit-md mb-unit-md">
            <div class="p-3 rounded-xl {{ $typeColor }}">
                <span class="material-symbols-outlined text-[28px]">{{ $typeIcon }}</span>
            </div>
            <div>
                <p class="font-headline-md text-headline-md text-on-surface">{{ $notification->title }}</p>
                <p class="font-label-sm text-label-sm text-outline">{{ $notification->created_at->format('d M Y, H:i') }}</p>
            </div>
        </div>

        <p class="font-body-lg text-body-lg text-on-surface mb-unit-lg">{{ $notification->message }}</p>

        @if(! $notification->is_read)
        <p class="inline-flex items-center gap-1 font-label-sm text-label-sm text-primary bg-surface-container-low px-3 py-1 rounded-full mb-unit-md">
            <span class="w-2 h-2 rounded-full bg-primary inline-block"></span> Unread
        </p>
        @endif

        @php $safeUrl = $notification->safeActionUrl(); @endphp
        @if($safeUrl)
        <a href="{{ $safeUrl }}"
           class="w-full flex items-center justify-center gap-2 bg-primary-container text-on-primary rounded-xl py-3 font-label-md text-label-md hover:opacity-90 active:scale-[0.98] transition-transform duration-100">
            <span class="material-symbols-outlined text-[18px]">open_in_new</span>
            View details
        </a>
        @endif
    </div>

</main>

</body>
</html>
