<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Notifications - HRIS Mobile App</title>
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
                "fontFamily": { "headline-md": ["Inter"], "body-md": ["Inter"], "label-md": ["Inter"], "label-sm": ["Inter"] },
                "fontSize": {
                    "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                    "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
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
    <button class="text-on-surface-variant hover:bg-surface-container active:scale-95 transition-transform duration-150 p-2 rounded-full flex items-center justify-center"
            onclick="history.back()">
        <span class="material-symbols-outlined">arrow_back</span>
    </button>
    <h1 class="font-headline-md text-headline-md font-bold text-primary">Notifications</h1>
    @if($notifications->total() > 0)
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf @method('PATCH')
        <button type="submit"
                class="text-primary font-label-md text-label-md hover:bg-surface-container-low active:scale-95 transition-transform duration-150 px-3 py-2 rounded-lg">
            Mark all read
        </button>
    </form>
    @else
    <div class="w-10"></div>
    @endif
</header>

<main class="flex-grow pt-[72px] px-container-margin pb-unit-xl">

    {{-- Flash --}}
    <x-flash-message class="mt-unit-md !mb-0" />

    <div class="mt-unit-md flex flex-col gap-card-gap">
        @forelse($notifications as $notification)
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
        <a href="{{ route('notifications.show', $notification) }}"
           class="flex items-start gap-unit-md p-unit-md rounded-xl border
                  {{ $notification->is_read ? 'bg-white border-border' : 'bg-surface-container-low border-primary/30' }}
                  hover:bg-surface-container transition-colors active:scale-[0.99] duration-100">
            <div class="p-2 rounded-lg flex-shrink-0 {{ $typeColor }}">
                <span class="material-symbols-outlined text-[20px]">{{ $typeIcon }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start gap-1 mb-1">
                    <p class="font-label-md text-label-md text-on-surface truncate">{{ $notification->title }}</p>
                    @if(! $notification->is_read)
                    <span class="flex-shrink-0 w-2 h-2 rounded-full bg-primary mt-1"></span>
                    @endif
                </div>
                <p class="font-body-md text-body-md text-on-surface-variant line-clamp-2">{{ $notification->message }}</p>
                <p class="font-label-sm text-label-sm text-outline mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
        </a>
        @empty
        <div class="flex flex-col items-center justify-center py-unit-xl opacity-40 select-none mt-unit-xl">
            <span class="material-symbols-outlined text-[64px]">notifications_off</span>
            <p class="font-label-md mt-2 text-on-surface-variant">No notifications yet.</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
    <div class="pt-unit-md">{{ $notifications->links() }}</div>
    @endif

</main>

</body>
</html>
