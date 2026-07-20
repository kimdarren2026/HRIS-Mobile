<!DOCTYPE html>
<html class="light" lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Ubah Lokasi Kantor - HRIS</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "primary":"#3525cd","on-primary":"#ffffff","primary-container":"#4f46e5","primary-fixed":"#e2dfff",
        "surface":"#F9FAFB","surface-container-low":"#f0f3ff","surface-container":"#e7eefe",
        "on-surface":"#151c27","on-surface-variant":"#464555","outline":"#777587","outline-variant":"#c7c4d8",
        "border":"#E5E7EB","background":"#f9f9ff","success":"#10B981","danger":"#EF4444","error":"#ba1a1a"
      },
      "fontFamily": {"body":["Inter"],"label":["Inter"],"headline":["Inter"]},
      "fontSize": {
        "headline-md-mobile":["18px",{"lineHeight":"24px","fontWeight":"600"}],
        "body-md":["14px",{"lineHeight":"20px","fontWeight":"400"}],
        "label-md":["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}]
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', sans-serif; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
</head>
<body class="bg-background text-on-surface flex flex-col items-center min-h-screen">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex items-center px-4 h-16">
<a href="{{ route('settings.index') }}" class="p-2 rounded-full hover:bg-surface-container transition-colors active:scale-95">
  <span class="material-symbols-outlined text-primary">arrow_back</span>
</a>
<h1 class="ml-3 font-bold text-primary text-headline-md-mobile">Ubah Lokasi Kantor</h1>
</header>

<main class="w-full max-w-[390px] mt-16 mb-8 px-4 py-4">
  <x-validation-errors variant="settings" />

  <form method="POST" action="{{ route('settings.locations.update', $officeLocation) }}" class="flex flex-col gap-4">
    @csrf
    @method('PUT')

    <div class="bg-white border border-border rounded-xl shadow-sm p-4 flex flex-col gap-4">

      <button type="button" id="use-current-location-btn"
        class="flex items-center justify-center gap-2 border border-primary text-primary font-label-md text-label-md py-2.5 rounded-lg active:scale-95 transition-transform hover:bg-primary/5">
        <span class="material-symbols-outlined text-[18px]">my_location</span>
        Gunakan Lokasi Saat Ini
      </button>
      <p id="geo-status" class="text-label-md text-on-surface-variant -mt-2 hidden"></p>

      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="name">Nama Kantor</label>
        <input type="text" id="name" name="name"
          value="{{ old('name', $officeLocation->name) }}"
          class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:outline-none focus:ring-2 focus:ring-primary/30"
          maxlength="100" required/>
        @error('name')<p class="text-error text-label-md mt-1">{{ $message }}</p>@enderror
      </div>

      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="latitude">Latitude</label>
        <input type="number" id="latitude" name="latitude" step="0.0000001"
          value="{{ old('latitude', $officeLocation->latitude) }}"
          class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:outline-none focus:ring-2 focus:ring-primary/30"
          min="-90" max="90" required/>
        @error('latitude')<p class="text-error text-label-md mt-1">{{ $message }}</p>@enderror
      </div>

      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="longitude">Longitude</label>
        <input type="number" id="longitude" name="longitude" step="0.0000001"
          value="{{ old('longitude', $officeLocation->longitude) }}"
          class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:outline-none focus:ring-2 focus:ring-primary/30"
          min="-180" max="180" required/>
        @error('longitude')<p class="text-error text-label-md mt-1">{{ $message }}</p>@enderror
      </div>

      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="radius_meters">Radius Absensi (meter)</label>
        <input type="number" id="radius_meters" name="radius_meters"
          value="{{ old('radius_meters', $officeLocation->radius_meters) }}"
          class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:outline-none focus:ring-2 focus:ring-primary/30"
          min="50" max="10000" required/>
        <p class="text-label-md text-on-surface-variant mt-0.5">Pegawai dalam radius ini otomatis disetujui saat check-in. Minimal 50m, maksimal 10000m.</p>
        @error('radius_meters')<p class="text-error text-label-md mt-1">{{ $message }}</p>@enderror
      </div>

      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" id="is_active" name="is_active" value="1"
          {{ old('is_active', $officeLocation->is_active) ? 'checked' : '' }}
          class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/30"/>
        <span class="font-body-md text-body-md">Jadikan lokasi kantor aktif</span>
      </label>
      <p class="text-label-md text-on-surface-variant -mt-2">Hanya satu lokasi kantor yang bisa aktif. Mengaktifkan lokasi ini akan menonaktifkan lokasi aktif saat ini (jika ada). Check-in pegawai selalu menggunakan lokasi yang aktif.</p>
      @error('is_active')<p class="text-error text-label-md mt-1">{{ $message }}</p>@enderror
    </div>

    <button type="submit"
      class="w-full bg-primary text-on-primary font-label-md text-label-md py-3 rounded-xl active:scale-95 transition-transform hover:bg-primary/90">
      Simpan Perubahan
    </button>
    <a href="{{ route('settings.index') }}"
      class="block text-center border border-outline-variant text-on-surface-variant font-label-md text-label-md py-3 rounded-xl hover:bg-surface-container transition-colors">
      Batal
    </a>
  </form>
</main>

<script>
(function() {
    var btn    = document.getElementById('use-current-location-btn');
    var status = document.getElementById('geo-status');
    if (!btn) return;

    function showStatus(msg, isError) {
        if (!status) return;
        status.textContent = msg;
        status.classList.remove('hidden');
        status.classList.toggle('text-error', !!isError);
        status.classList.toggle('text-on-surface-variant', !isError);
    }

    btn.addEventListener('click', function() {
        if (!navigator.geolocation) {
            showStatus('Geolocation tidak didukung oleh browser ini. Silakan isi koordinat secara manual.', true);
            return;
        }

        showStatus('Mendeteksi lokasi saat ini...', false);

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                document.getElementById('latitude').value  = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;
                showStatus('Lokasi terdeteksi (akurasi: ' + Math.round(pos.coords.accuracy) + 'm). Anda tetap bisa mengubah nilainya secara manual.', false);
            },
            function(err) {
                var msgs = {
                    1: 'Izin lokasi ditolak. Aktifkan akses lokasi di pengaturan browser, atau isi koordinat secara manual.',
                    2: 'Lokasi tidak tersedia. Silakan isi koordinat secara manual.',
                    3: 'Permintaan lokasi timeout. Coba lagi atau isi koordinat secara manual.',
                };
                showStatus(msgs[err.code] || 'Tidak dapat memperoleh lokasi saat ini. Silakan isi koordinat secara manual.', true);
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    });
})();
</script>
</body></html>
