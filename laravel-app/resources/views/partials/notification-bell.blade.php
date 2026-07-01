<a href="{{ route('notifications.index') . '?return_url=' . urlencode(request()->getRequestUri()) }}"
   class="{{ $class ?? '' }}"
   aria-label="Notifikasi">
    <span class="material-symbols-outlined">notifications</span>
    @if(($unreadNotificationCount ?? 0) > 0)
    <span class="{{ $badgeClass ?? 'absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-error text-white text-[10px] font-bold flex items-center justify-center' }}">
        {{ ($unreadNotificationCount ?? 0) > 99 ? '99+' : ($unreadNotificationCount ?? 0) }}
    </span>
    @endif
</a>
