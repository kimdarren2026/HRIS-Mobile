{{--
    Shared bottom navigation for the super_admin-only pages that use it: the
    audit log (index + show) and the Phase 60B Dashboard Disiplin Kehadiran.

    Phase 60C: the active item is derived from the current route name instead of
    being hardcoded, because this nav is shared — the discipline dashboard has to
    highlight "Laporan", not "Audit". Route names are matched exactly (never a
    broad 'admin/*' path match), so Audit and Laporan can never light up at the
    same time.
--}}
@php
    $navIdle   = 'flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2';
    $navActive = 'flex flex-col items-center justify-center text-primary bg-secondary-fixed rounded-xl px-3 py-1 transition-transform active:scale-95 duration-150';

    $auditIsActive   = request()->routeIs('audit-logs.index', 'audit-logs.show');
    $reportsIsActive = request()->routeIs('admin.attendance-discipline.index');
@endphp
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
  <a class="{{ $navIdle }}" href="{{ route('admin.dashboard') }}">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_home') }}</span>
  </a>
  <a class="{{ $navIdle }}" href="{{ route('admin.users.index') }}">
    <span class="material-symbols-outlined">manage_accounts</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_users') }}</span>
  </a>
  <a class="{{ $auditIsActive ? $navActive : $navIdle }}" href="{{ route('audit-logs.index') }}"
     @if($auditIsActive) aria-current="page" @endif>
    <span class="material-symbols-outlined" @if($auditIsActive) style="font-variation-settings:'FILL' 1" @endif>shield</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_audit') }}</span>
  </a>
  <a class="{{ $reportsIsActive ? $navActive : $navIdle }}" href="{{ \App\Support\ReportsNavigation::url() }}"
     @if($reportsIsActive) aria-current="page" @endif>
    <span class="material-symbols-outlined" @if($reportsIsActive) style="font-variation-settings:'FILL' 1" @endif>assessment</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_reports') }}</span>
  </a>
  <a class="{{ $navIdle }}" href="/profile">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_profile') }}</span>
  </a>
</nav>
