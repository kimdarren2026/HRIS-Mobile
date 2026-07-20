<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>{{ isset($employee) ? 'Ubah Pegawai' : 'Tambah Pegawai' }} - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "surface":"#F9FAFB","surface-container-lowest":"#ffffff","surface-container-low":"#f0f3ff",
        "surface-container":"#e7eefe","surface-container-high":"#e2e8f8","surface-container-highest":"#dce2f3",
        "on-surface":"#151c27","on-surface-variant":"#464555","outline":"#777587","outline-variant":"#c7c4d8",
        "primary":"#3525cd","primary-container":"#4f46e5","primary-fixed":"#e2dfff",
        "on-primary":"#ffffff","on-primary-container":"#dad7ff",
        "secondary":"#4e45d5","secondary-container":"#6860ef","on-secondary":"#ffffff",
        "border":"#E5E7EB","background":"#f9f9ff","success":"#10B981","warning":"#F59E0B","danger":"#EF4444"
      },
      "borderRadius": {"DEFAULT":"0.25rem","lg":"0.5rem","xl":"0.75rem","full":"9999px"},
      "spacing": {"unit-xs":"4px","unit-sm":"8px","unit-md":"16px","unit-lg":"24px","unit-xl":"32px","card-gap":"12px","container-margin":"16px"},
      "fontFamily": {"label-sm":["Inter"],"label-md":["Inter"],"body-md":["Inter"],"body-lg":["Inter"],"headline-md":["Inter"],"headline-lg":["Inter"]},
      "fontSize": {
        "label-sm":["11px",{"lineHeight":"14px","fontWeight":"500"}],
        "label-md":["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}],
        "body-md":["14px",{"lineHeight":"20px","fontWeight":"400"}],
        "body-lg":["16px",{"lineHeight":"24px","fontWeight":"400"}],
        "headline-md":["20px",{"lineHeight":"28px","fontWeight":"600"}],
        "headline-lg":["24px",{"lineHeight":"32px","fontWeight":"700"}]
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
  .field-label { @apply font-label-sm text-on-surface-variant uppercase tracking-wider mb-1 block; font-size: 11px; line-height: 14px; font-weight: 500; letter-spacing: 0.05em; color: #464555; display: block; margin-bottom: 4px; }
  .field-input { width: 100%; background: white; border: 1px solid #c7c4d8; border-radius: 0.75rem; padding: 10px 14px; font-size: 14px; line-height: 20px; color: #151c27; outline: none; }
  .field-input:focus { border-color: #3525cd; box-shadow: 0 0 0 2px rgba(53,37,205,0.15); }
  .field-error { font-size: 12px; color: #EF4444; margin-top: 4px; }
</style>
</head>
<body class="bg-surface text-on-surface min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-24">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex items-center px-container-margin h-16 gap-3">
  <a href="{{ isset($employee) ? route('employees.show', $employee) : route('employees.index') }}" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary">{{ isset($employee) ? 'Ubah Pegawai' : 'Tambah Pegawai' }}</h1>
</header>

<main class="mt-16 px-container-margin pt-unit-lg pb-unit-xl">

  @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-danger rounded-xl px-4 py-3 mb-unit-md">
      <p class="font-label-md text-label-md mb-1">Perbaiki kesalahan berikut:</p>
      <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
          <li class="font-body-md text-body-md">{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(isset($employee))
    <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-unit-md">
      @csrf @method('PUT')
  @else
    <form method="POST" action="{{ route('employees.store') }}" class="space-y-unit-md">
      @csrf
  @endif

    {{-- User account section (create only) --}}
    @unless(isset($employee))
    <section class="bg-surface-container-lowest rounded-xl border border-border p-unit-md shadow-sm space-y-unit-md">
      <h2 class="font-label-md text-label-md text-outline uppercase tracking-widest">Detail Akun</h2>

      <div>
        <label class="field-label" for="name">Nama Lengkap *</label>
        <input class="field-input" id="name" name="name" type="text" value="{{ old('name') }}" placeholder="John Doe" required>
        @error('name') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="email">Email *</label>
        <input class="field-input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="john@company.com" required>
        @error('email') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="password">Password *</label>
        <input class="field-input" id="password" name="password" type="password" placeholder="Minimal 8 karakter" required>
        @error('password') <p class="field-error">{{ $message }}</p> @enderror
      </div>
    </section>
    @endunless

    {{-- Employment section --}}
    <section class="bg-surface-container-lowest rounded-xl border border-border p-unit-md shadow-sm space-y-unit-md">
      <h2 class="font-label-md text-label-md text-outline uppercase tracking-widest">Detail Kepegawaian</h2>

      <div>
        <label class="field-label" for="nik">NIK (ID Pegawai) *</label>
        <input class="field-input" id="nik" name="nik" type="text"
          value="{{ old('nik', $employee->nik ?? '') }}" placeholder="EMP-2026-001" required>
        @error('nik') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="department_id">Departemen *</label>
        <div class="relative">
          <select class="field-input appearance-none pr-8" id="department_id" name="department_id" required>
            <option value="">Pilih Departemen</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                {{ $dept->name }}
              </option>
            @endforeach
          </select>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant text-[18px]">expand_more</span>
        </div>
        @error('department_id') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="position_id">Posisi *</label>
        <div class="relative">
          <select class="field-input appearance-none pr-8" id="position_id" name="position_id" required>
            <option value="">Pilih Posisi</option>
            @foreach($positions as $pos)
              <option value="{{ $pos->id }}" {{ old('position_id', $employee->position_id ?? '') == $pos->id ? 'selected' : '' }}>
                {{ $pos->name }} ({{ $pos->department->name }})
              </option>
            @endforeach
          </select>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant text-[18px]">expand_more</span>
        </div>
        @error('position_id') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="join_date">Tanggal Bergabung *</label>
        <input class="field-input" id="join_date" name="join_date" type="date"
          value="{{ old('join_date', isset($employee) ? $employee->join_date?->format('Y-m-d') : '') }}" required>
        @error('join_date') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="employment_status">Status *</label>
        <div class="relative">
          <select class="field-input appearance-none pr-8" id="employment_status" name="employment_status" required>
            @php
              $statusLabels = ['active' => 'Aktif', 'probation' => 'Masa Percobaan', 'resigned' => 'Mengundurkan Diri', 'terminated' => 'Diberhentikan'];
            @endphp
            @foreach(['active','probation','resigned','terminated'] as $s)
              <option value="{{ $s }}" {{ old('employment_status', $employee->employment_status ?? 'active') === $s ? 'selected' : '' }}>
                {{ $statusLabels[$s] }}
              </option>
            @endforeach
          </select>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-on-surface-variant text-[18px]">expand_more</span>
        </div>
        @error('employment_status') <p class="field-error">{{ $message }}</p> @enderror
      </div>
    </section>

    {{-- Contact section --}}
    <section class="bg-surface-container-lowest rounded-xl border border-border p-unit-md shadow-sm space-y-unit-md">
      <h2 class="font-label-md text-label-md text-outline uppercase tracking-widest">Kontak</h2>

      <div>
        <label class="field-label" for="phone_number">Nomor Telepon *</label>
        <input class="field-input" id="phone_number" name="phone_number" type="tel"
          value="{{ old('phone_number', $employee->phone_number ?? '') }}" placeholder="+62812345678" required>
        @error('phone_number') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="address">Alamat</label>
        <textarea class="field-input" id="address" name="address" rows="3" placeholder="Alamat lengkap...">{{ old('address', $employee->address ?? '') }}</textarea>
        @error('address') <p class="field-error">{{ $message }}</p> @enderror
      </div>
    </section>

    {{-- Bank section --}}
    <section class="bg-surface-container-lowest rounded-xl border border-border p-unit-md shadow-sm space-y-unit-md">
      <h2 class="font-label-md text-label-md text-outline uppercase tracking-widest">Rekening Bank</h2>

      <div>
        <label class="field-label" for="bank_name">Nama Bank</label>
        <input class="field-input" id="bank_name" name="bank_name" type="text"
          value="{{ old('bank_name', $employee->bank_name ?? '') }}" placeholder="BCA, Mandiri, BNI...">
        @error('bank_name') <p class="field-error">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="field-label" for="bank_account_number">Nomor Rekening</label>
        <input class="field-input" id="bank_account_number" name="bank_account_number" type="text"
          value="{{ old('bank_account_number') }}" placeholder="1234567890">
        @if(isset($employee) && $employee->bank_account_number)
          <p class="text-xs text-on-surface-variant mt-1">Biarkan kosong untuk mempertahankan nomor rekening yang sudah tersimpan.</p>
        @endif
        @error('bank_account_number') <p class="field-error">{{ $message }}</p> @enderror
      </div>
    </section>

    <button type="submit"
      class="w-full h-14 bg-primary text-on-primary font-semibold rounded-xl shadow-lg flex items-center justify-center gap-2 hover:opacity-90 active:scale-95 transition-all">
      <span class="material-symbols-outlined text-[20px]">{{ isset($employee) ? 'save' : 'person_add' }}</span>
      {{ isset($employee) ? 'Simpan Perubahan' : 'Tambah Pegawai' }}
    </button>

  </form>
</main>

</body></html>
