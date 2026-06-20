<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Add Leave Type - HRIS</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "primary":"#3525cd","on-primary":"#ffffff","primary-container":"#4f46e5",
        "surface":"#F9FAFB","surface-container-low":"#f0f3ff","surface-container":"#e7eefe",
        "on-surface":"#151c27","on-surface-variant":"#464555","outline":"#777587","outline-variant":"#c7c4d8",
        "border":"#E5E7EB","background":"#f9f9ff","error":"#ba1a1a"
      },
      "fontSize": {
        "headline-md-mobile":["18px",{"lineHeight":"24px","fontWeight":"600"}],
        "body-md":["14px",{"lineHeight":"20px","fontWeight":"400"}],
        "label-md":["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}]
      }
    }
  }
}
</script>
<style>body { font-family: 'Inter', sans-serif; } .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }</style>
</head>
<body class="bg-background text-on-surface flex flex-col items-center min-h-screen">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex items-center px-4 h-16">
<a href="{{ route('settings.leave-types.index') }}" class="p-2 rounded-full hover:bg-surface-container transition-colors active:scale-95">
  <span class="material-symbols-outlined text-primary">arrow_back</span>
</a>
<h1 class="ml-3 font-bold text-primary text-headline-md-mobile">Add Leave Type</h1>
</header>

<main class="w-full max-w-[390px] mt-16 mb-8 px-4 py-4">
  @if($errors->any())
  <div class="mb-4 bg-red-50 border border-red-200 text-error rounded-lg px-4 py-3 text-body-md">
    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
  </div>
  @endif

  <form method="POST" action="{{ route('settings.leave-types.store') }}" class="flex flex-col gap-4">
    @csrf

    <div class="bg-white border border-border rounded-xl shadow-sm p-4 flex flex-col gap-4">
      <div class="flex flex-col gap-1">
        <label class="font-label-md text-label-md text-on-surface-variant" for="name">Leave Type Name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}"
          class="border border-outline-variant rounded-lg px-3 py-2 text-body-md focus:outline-none focus:ring-2 focus:ring-primary/30"
          maxlength="100" placeholder="e.g. Annual Leave" required/>
        @error('name')<p class="text-error text-label-md mt-1">{{ $message }}</p>@enderror
      </div>

      <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="deducts_balance" value="1"
          {{ old('deducts_balance', '1') ? 'checked' : '' }}
          class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary/30"/>
        <div class="flex flex-col">
          <span class="font-body-md text-body-md">Deducts from balance</span>
          <span class="font-label-md text-label-md text-on-surface-variant mt-0.5">Tracks remaining quota for this leave type</span>
        </div>
      </label>
    </div>

    <button type="submit"
      class="w-full bg-primary text-on-primary font-label-md text-label-md py-3 rounded-xl active:scale-95 transition-transform">
      Create Leave Type
    </button>
    <a href="{{ route('settings.leave-types.index') }}"
      class="block text-center border border-outline-variant text-on-surface-variant font-label-md text-label-md py-3 rounded-xl hover:bg-surface-container transition-colors">
      Cancel
    </a>
  </form>
</main>
</body></html>
