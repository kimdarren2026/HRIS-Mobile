<!DOCTYPE html>
<html class="light" lang="id">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Manajemen Pengguna & Peran - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
            "surface-container-lowest": "#ffffff","surface-container-low": "#f0f3ff",
            "surface-container": "#e7eefe","surface-container-high": "#e2e8f8",
            "surface-container-highest": "#dce2f3","surface-dim": "#d3daea",
            "surface-bright": "#f9f9ff","surface-variant": "#dce2f3",
            "surface": "#F9FAFB","background": "#f9f9ff",
            "on-surface": "#151c27","on-surface-variant": "#464555",
            "on-background": "#151c27","outline": "#777587","outline-variant": "#c7c4d8",
            "primary": "#3525cd","primary-fixed": "#e2dfff","primary-fixed-dim": "#c3c0ff",
            "primary-container": "#4f46e5","on-primary": "#ffffff","on-primary-fixed": "#0f0069",
            "on-primary-fixed-variant": "#3323cc","on-primary-container": "#dad7ff",
            "secondary": "#4e45d5","secondary-fixed": "#e3dfff","secondary-fixed-dim": "#c3c0ff",
            "secondary-container": "#6860ef","on-secondary": "#ffffff",
            "on-secondary-fixed": "#100069","on-secondary-fixed-variant": "#372abf",
            "on-secondary-container": "#fffbff","surface-tint": "#4d44e3",
            "inverse-surface": "#2a313d","inverse-on-surface": "#ebf1ff","inverse-primary": "#c3c0ff",
            "tertiary": "#7e3000","tertiary-fixed": "#ffdbcc","tertiary-fixed-dim": "#ffb695",
            "tertiary-container": "#a44100","on-tertiary": "#ffffff","on-tertiary-fixed": "#351000",
            "on-tertiary-fixed-variant": "#7b2f00","on-tertiary-container": "#ffd2be",
            "error": "#ba1a1a","error-container": "#ffdad6","on-error": "#ffffff","on-error-container": "#93000a",
            "success": "#10B981","warning": "#F59E0B","danger": "#EF4444","border": "#E5E7EB"
          },
          "borderRadius": {"DEFAULT": "0.25rem","lg": "0.5rem","xl": "0.75rem","full": "9999px"},
          "spacing": {
            "container-margin": "16px","unit-xs": "4px","unit-sm": "8px",
            "unit-md": "16px","unit-lg": "24px","unit-xl": "32px","card-gap": "12px"
          },
          "fontFamily": {
            "label-md": ["Inter"],"label-sm": ["Inter"],"headline-md": ["Inter"],
            "body-md": ["Inter"],"body-lg": ["Inter"],"status-badge": ["Inter"],"headline-lg": ["Inter"]
          },
          "fontSize": {
            "headline-md": ["20px",{"lineHeight":"28px","fontWeight":"600"}],
            "headline-md-mobile": ["18px",{"lineHeight":"24px","fontWeight":"600"}],
            "body-md": ["14px",{"lineHeight":"20px","fontWeight":"400"}],
            "body-lg": ["16px",{"lineHeight":"24px","fontWeight":"400"}],
            "status-badge": ["12px",{"lineHeight":"12px","fontWeight":"700"}],
            "label-md": ["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}],
            "label-sm": ["11px",{"lineHeight":"14px","fontWeight":"500"}]
          }
        },
      },
    }
</script>
<style>
    body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

{{-- Header --}}
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex items-center px-container-margin gap-3">
  <a href="/admin/dashboard" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary flex-1 truncate">Manajemen Pengguna</h1>
  <span class="font-label-sm text-label-sm text-on-surface-variant">Super Admin</span>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  {{-- Flash messages --}}
  @if(session('success'))
    <div class="flex items-center gap-2 rounded-lg border border-success/30 bg-success/10 px-4 py-3 font-body-md text-body-md text-success">
      <span class="material-symbols-outlined text-[18px]">check_circle</span>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  @if(session('error'))
    <div class="flex items-center gap-2 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 font-body-md text-body-md text-danger">
      <span class="material-symbols-outlined text-[18px]">error</span>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  {{-- Summary chip --}}
  <div class="flex items-center gap-2 text-on-surface-variant">
    <span class="material-symbols-outlined text-primary text-[20px]">manage_accounts</span>
    <span class="font-label-md text-label-md uppercase tracking-wide">{{ $users->count() }} Pengguna</span>
  </div>

  {{-- User cards --}}
  @foreach($users as $user)
    @php
      $roleColors = [
        'super_admin' => 'text-primary bg-primary/10 border-primary/20',
        'admin_hr'    => 'text-secondary bg-secondary/10 border-secondary/20',
        'finance'     => 'text-warning bg-warning/10 border-warning/20',
        'employee'    => 'text-on-surface-variant bg-surface-container border-outline-variant/30',
      ];
      $roleColor = $roleColors[$user->role] ?? 'text-on-surface-variant bg-surface-container border-outline-variant/30';
      $isCurrentUser = $user->id === auth()->id();
    @endphp

    <section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-unit-md flex flex-col gap-unit-sm">
      {{-- User info row --}}
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-primary text-[20px]">person</span>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-body-lg text-body-lg font-semibold text-on-surface truncate">{{ $user->name }}</span>
            @if($isCurrentUser)
              <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-primary text-white">ANDA</span>
            @endif
          </div>
          <span class="font-body-md text-body-md text-on-surface-variant truncate block">{{ $user->email }}</span>
          @if($user->employee)
            <span class="font-label-sm text-label-sm text-outline">NIK: {{ $user->employee->nik }}</span>
          @else
            <span class="font-label-sm text-label-sm text-outline italic">Tidak ada pegawai terkait</span>
          @endif
        </div>
        {{-- Active status badge --}}
        <div>
          @if($user->is_active)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-success/10 text-success border border-success/20">
              <span class="material-symbols-outlined text-[12px]" style="font-variation-settings:'FILL' 1">check_circle</span>Aktif
            </span>
          @else
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-danger/10 text-danger border border-danger/20">
              <span class="material-symbols-outlined text-[12px]">cancel</span>Tidak Aktif
            </span>
          @endif
        </div>
      </div>

      {{-- Current role badge --}}
      <div class="flex items-center gap-2">
        <span class="font-label-md text-label-md text-on-surface-variant uppercase">Peran saat ini:</span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $roleColor }}">
          {{ strtoupper(__('common.role_labels')[$user->role] ?? $user->role) }}
        </span>
      </div>

      {{-- Update role form --}}
      <form method="POST" action="{{ route('admin.users.update-role', $user) }}"
            class="flex items-center gap-2 mt-1"
            onsubmit="return confirm('Ubah peran untuk {{ addslashes($user->name) }}?')">
        @csrf
        @method('PATCH')
        <select name="role"
                class="flex-1 border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
          @foreach(['employee','admin_hr','finance','super_admin'] as $role)
            <option value="{{ $role }}" @selected($user->role === $role)>
              {{ __('common.role_labels')[$role] ?? $role }}
            </option>
          @endforeach
        </select>
        <button type="submit"
                class="shrink-0 bg-primary text-white px-3 py-2 rounded-lg font-label-md text-label-md active:opacity-80 transition-opacity">
          Perbarui
        </button>
      </form>

      {{-- Toggle active status --}}
      <form method="POST" action="{{ route('admin.users.update-status', $user) }}"
            class="mt-1"
            onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ addslashes($user->name) }}?')">
        @csrf
        @method('PATCH')
        <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 py-2 rounded-lg border transition-opacity active:opacity-80 font-label-md text-label-md
                  {{ $user->is_active
                      ? 'border-danger/30 bg-danger/5 text-danger'
                      : 'border-success/30 bg-success/5 text-success' }}">
          <span class="material-symbols-outlined text-[16px]">{{ $user->is_active ? 'person_off' : 'person' }}</span>
          {{ $user->is_active ? 'Nonaktifkan Pengguna' : 'Aktifkan Pengguna' }}
        </button>
      </form>
    </section>
  @endforeach

</main>

{{-- Bottom nav --}}
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
  <a href="/admin/dashboard" class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_home') }}</span>
  </a>
  <a href="{{ route('admin.users.index') }}" class="flex flex-col items-center justify-center text-primary bg-secondary-fixed rounded-xl px-3 py-1 transition-transform active:scale-95 duration-150">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">manage_accounts</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_users') }}</span>
  </a>
  <a href="{{ route('audit-logs.index') }}" class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2">
    <span class="material-symbols-outlined">shield</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_audit') }}</span>
  </a>
  <a href="/profile" class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_profile') }}</span>
  </a>
</nav>

</body>
</html>
