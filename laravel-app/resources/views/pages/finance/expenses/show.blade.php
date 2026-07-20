<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>{{ $expense->title }} - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "secondary-fixed": "#e3dfff","surface-container-low": "#f0f3ff","inverse-surface": "#2a313d",
                  "surface-variant": "#dce2f3","surface": "#F9FAFB","surface-container-high": "#e2e8f8",
                  "surface-container-lowest": "#ffffff","on-surface": "#151c27","surface-container": "#e7eefe",
                  "warning": "#F59E0B","on-secondary": "#ffffff","background": "#f9f9ff",
                  "primary-fixed": "#e2dfff","on-surface-variant": "#464555","danger": "#EF4444",
                  "primary-container": "#4f46e5","surface-container-highest": "#dce2f3","surface-tint": "#4d44e3",
                  "on-primary-container": "#dad7ff","on-primary": "#ffffff","primary": "#3525cd",
                  "on-error": "#ffffff","success": "#10B981","border": "#E5E7EB",
                  "primary-fixed-dim": "#c3c0ff","secondary-container": "#6860ef","secondary": "#4e45d5",
                  "outline-variant": "#c7c4d8"
          },
          "borderRadius": {"DEFAULT": "0.25rem","lg": "0.5rem","xl": "0.75rem","full": "9999px"},
          "spacing": {"unit-xs": "4px","card-gap": "12px","unit-md": "16px","container-margin": "16px","unit-xl": "32px","unit-sm": "8px","unit-lg": "24px"},
          "fontFamily": {"label-md": ["Inter"],"label-sm": ["Inter"],"headline-md": ["Inter"],"body-lg": ["Inter"],"status-badge": ["Inter"],"headline-lg": ["Inter"],"body-md": ["Inter"]},
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
    body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex items-center px-container-margin gap-3">
  <a href="{{ route('finance.expenses.index') }}" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary flex-1 truncate">Detail Pengeluaran</h1>
  @can('update', $expense)
    <a href="{{ route('finance.expenses.edit', $expense) }}" class="text-primary p-1">
      <span class="material-symbols-outlined">edit</span>
    </a>
  @endcan
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 font-body-md text-body-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-success">check_circle</span>
      {{ session('success') }}
    </div>
  @endif

  @php
    $statusColor = match($expense->status) {
      'DRAFT'     => 'bg-gray-100 text-gray-600',
      'SUBMITTED' => 'bg-blue-100 text-blue-700',
      'APPROVED'  => 'bg-orange-100 text-orange-700',
      'REJECTED'  => 'bg-red-100 text-red-700',
      'PAID'      => 'bg-green-100 text-green-700',
      default     => 'bg-gray-100 text-gray-600',
    };
  @endphp

  {{-- Header Card --}}
  <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <div class="flex justify-between items-start">
      <div class="flex-1 min-w-0 pr-2">
        <p class="font-headline-md text-headline-md text-on-surface">{{ $expense->title }}</p>
        <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $expense->expense_number }}</p>
      </div>
      <span class="{{ $statusColor }} px-3 py-1 rounded-full font-status-badge text-status-badge flex-shrink-0">{{ __('finance.status_labels')[$expense->status] ?? $expense->status }}</span>
    </div>
    <div class="flex justify-between items-center border-t border-border pt-3">
      <p class="font-label-md text-label-md text-on-surface-variant">{{ __('finance.category_labels')[$expense->category] ?? $expense->category }}</p>
      <p class="font-headline-md text-headline-md text-primary">Rp {{ number_format($expense->amount, 0, ',', '.') }}</p>
    </div>
  </div>

  {{-- Details --}}
  <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <h2 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Detail</h2>

    <div class="grid grid-cols-2 gap-unit-sm">
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Tanggal Pengeluaran</p>
        <p class="font-body-md text-body-md text-on-surface">{{ $expense->expense_date->translatedFormat('d M Y') }}</p>
      </div>
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Penerima</p>
        <p class="font-body-md text-body-md text-on-surface">{{ $expense->recipient_name }}</p>
      </div>
    </div>

    @if($expense->employee)
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Pegawai Terkait</p>
        <p class="font-body-md text-body-md text-on-surface">{{ $expense->employee->user?->name ?? $expense->employee->nik }}</p>
      </div>
    @endif

    @if($expense->cost_center)
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Cost Center</p>
        <p class="font-body-md text-body-md text-on-surface">{{ $expense->cost_center }}</p>
      </div>
    @endif

    @if($expense->description)
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Deskripsi</p>
        <p class="font-body-md text-body-md text-on-surface">{{ $expense->description }}</p>
      </div>
    @endif

    @if($expense->receipt_path)
      <div>
        <p class="font-label-sm text-label-sm text-on-surface-variant">Kwitansi</p>
        <a href="{{ route('finance.expenses.receipt', $expense) }}" target="_blank"
           class="inline-flex items-center gap-1 text-primary font-label-md text-label-md">
          <span class="material-symbols-outlined text-[16px]">attach_file</span>
          Lihat Kwitansi
        </a>
      </div>
    @endif

    <div>
      <p class="font-label-sm text-label-sm text-on-surface-variant">Dibuat Oleh</p>
      <p class="font-body-md text-body-md text-on-surface">{{ $expense->creator?->name ?? '—' }}</p>
    </div>
  </div>

  {{-- Payment Info (for PAID status) --}}
  @if($expense->status === 'PAID')
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex flex-col gap-2">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-success">check_circle</span>
        <p class="font-label-md text-label-md text-green-800">Pembayaran Tercatat</p>
      </div>
      @if($expense->paid_at)
        <p class="font-label-sm text-label-sm text-on-surface-variant">Dibayar: {{ $expense->paid_at->translatedFormat('d M Y, H:i') }}</p>
      @endif
      @if($expense->payment_reference)
        <p class="font-label-sm text-label-sm text-on-surface-variant">Referensi: {{ $expense->payment_reference }}</p>
      @endif
      <p class="font-label-sm text-label-sm text-amber-700">Hanya catatan pembayaran — tidak ada transfer bank nyata yang dilakukan.</p>
    </div>
  @endif

  {{-- Rejection Note --}}
  @if($expense->status === 'REJECTED' && $expense->rejection_note)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
      <p class="font-label-md text-label-md text-red-800 mb-1">Alasan Penolakan</p>
      <p class="font-body-md text-body-md text-red-700">{{ $expense->rejection_note }}</p>
    </div>
  @endif

  {{-- Approval Info --}}
  @if($expense->approver && in_array($expense->status, ['APPROVED', 'PAID']))
    <div class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-2">
      <p class="font-label-sm text-label-sm text-on-surface-variant">Disetujui Oleh</p>
      <p class="font-body-md text-body-md text-on-surface">{{ $expense->approver->name }}</p>
      @if($expense->approved_at)
        <p class="font-label-sm text-label-sm text-on-surface-variant">{{ $expense->approved_at->translatedFormat('d M Y, H:i') }}</p>
      @endif
    </div>
  @endif

  {{-- Actions --}}
  <div class="flex flex-col gap-unit-sm">

    @can('submit', $expense)
      <form method="POST" action="{{ route('finance.expenses.submit', $expense) }}"
        onsubmit="return confirm('Ajukan pengeluaran ini untuk persetujuan?')">
        @csrf
        <button type="submit" class="w-full bg-primary text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
          <span class="material-symbols-outlined">send</span>
          Ajukan untuk Persetujuan
        </button>
      </form>
    @endcan

    @can('approve', $expense)
      <form method="POST" action="{{ route('finance.expenses.approve', $expense) }}"
        onsubmit="return confirm('Setujui pengeluaran ini?')">
        @csrf
        <button type="submit" class="w-full bg-success text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
          <span class="material-symbols-outlined">thumb_up</span>
          Setujui
        </button>
      </form>

      <form method="POST" action="{{ route('finance.expenses.reject', $expense) }}" class="flex flex-col gap-unit-sm">
        @csrf
        <textarea name="rejection_note" rows="2" required minlength="10" maxlength="1000"
          placeholder="Alasan penolakan (wajib, minimal 10 karakter)"
          class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-danger resize-none"></textarea>
        <button type="submit" class="w-full bg-danger text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
          <span class="material-symbols-outlined">thumb_down</span>
          Tolak
        </button>
      </form>
    @endcan

    @can('markPaid', $expense)
      <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 font-body-md text-body-md text-amber-800 flex items-start gap-2">
        <span class="material-symbols-outlined text-[18px] mt-0.5 flex-shrink-0">info</span>
        <span>"Tandai Sudah Dibayar" hanya mencatat status pembayaran — <strong>tidak</strong> melakukan transfer bank nyata.</span>
      </div>
      <form method="POST" action="{{ route('finance.expenses.mark-paid', $expense) }}"
        onsubmit="return confirm('Tandai pengeluaran ini sudah dibayar? Ini hanya mencatat status.')">
        @csrf
        <div class="flex flex-col gap-unit-sm">
          <input type="text" name="payment_reference" maxlength="100" placeholder="Referensi pembayaran (opsional)"
            class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
          <button type="submit" class="w-full bg-success text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90">
            <span class="material-symbols-outlined">payments</span>
            Tandai Sudah Dibayar
          </button>
        </div>
      </form>
    @endcan

  </div>

</main>

<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-border shadow-lg flex justify-around items-center h-18 pb-safe px-unit-xs">
  @php($navRole = auth()->user()->role)
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="{{ $navRole === 'finance' ? '/finance/dashboard' : '/admin/dashboard' }}">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_home') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center text-primary bg-secondary-fixed rounded-xl px-3 py-1 transition-transform active:scale-95 duration-150" href="{{ route('finance.expenses.index') }}">
    <span class="material-symbols-outlined">account_balance_wallet</span>
    <span class="font-label-sm text-label-sm">Pengeluaran</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant transition-transform active:scale-95 duration-150 py-2" href="/profile">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm">{{ __('common.nav_profile') }}</span>
  </a>
</nav>

</body></html>
