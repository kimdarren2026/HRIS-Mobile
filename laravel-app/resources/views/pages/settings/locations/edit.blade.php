<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Edit Office Location - HRIS</title>
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
<h1 class="ml-3 font-bold text-primary text-headline-md-mobile">Edit Office Location</h1>
</header>

<main class="w-full max-w-[390px] mt-16 mb-8 px-4 py-4">
  @if($errors->any())
  <div class="mb-4 bg-red-50 border border-red-200 text-error rounded-lg px-4 py-3 text-body-md">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
  </div>
  @endif

  <form method="POST" action="{{ route('settings.locations.update', $officeLocation) }}" class="flex flex-col gap-4">
    @csrf
    @method('PUT')

    <div class="bg-white border border-border rounded-xl shadow-sm p-4 flex flex-col gap-4">

      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="name">Office Name</label>
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
        <label class="font-label-md text-label-md text-on-surface-variant" for="radius_meters">Check-in Radius (meters)</label>
        <input type="number" id="radius_meters" name="radius_meters"
          value="{{ old('radius_meters', $officeLocation->radius_meters) }}"
          class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:outline-none focus:ring-2 focus:ring-primary/30"
          min="50" max="10000" required/>
        <p class="text-label-md text-on-surface-variant mt-0.5">Employees within this radius auto-approve on check-in. Min 50m, max 10000m.</p>
        @error('radius_meters')<p class="text-error text-label-md mt-1">{{ $message }}</p>@enderror
      </div>
    </div>

    <button type="submit"
      class="w-full bg-primary text-on-primary font-label-md text-label-md py-3 rounded-xl active:scale-95 transition-transform hover:bg-primary/90">
      Save Changes
    </button>
    <a href="{{ route('settings.index') }}"
      class="block text-center border border-outline-variant text-on-surface-variant font-label-md text-label-md py-3 rounded-xl hover:bg-surface-container transition-colors">
      Cancel
    </a>
  </form>
</main>
</body></html>
