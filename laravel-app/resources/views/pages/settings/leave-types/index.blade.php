<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Leave Types - HRIS</title>
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
        "label-md":["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}],
        "label-sm":["11px",{"lineHeight":"14px","fontWeight":"500"}]
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

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex items-center justify-between px-4 h-16">
<a href="{{ route('settings.index') }}" class="p-2 rounded-full hover:bg-surface-container transition-colors active:scale-95">
  <span class="material-symbols-outlined text-primary">arrow_back</span>
</a>
<h1 class="font-bold text-primary text-headline-md-mobile">Leave Types</h1>
<a href="{{ route('settings.leave-types.create') }}" class="p-2 rounded-full hover:bg-surface-container transition-colors active:scale-95">
  <span class="material-symbols-outlined text-primary">add</span>
</a>
</header>

<main class="w-full max-w-[390px] mt-16 mb-8 px-4 py-4 flex flex-col gap-3">
  @if(session('success'))
  <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-body-md flex items-center gap-2">
    <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1;">check_circle</span>
    {{ session('success') }}
  </div>
  @endif
  @if($errors->has('general'))
  <div class="bg-red-50 border border-red-200 text-error rounded-lg px-4 py-3 text-body-md">
    {{ $errors->first('general') }}
  </div>
  @endif

  @forelse($leaveTypes as $leaveType)
  <div class="bg-white border border-border rounded-xl shadow-sm p-4 flex items-center justify-between">
    <div class="flex flex-col">
      <span class="font-body-md text-body-md font-medium">{{ $leaveType->name }}</span>
      <span class="font-label-sm text-label-sm text-on-surface-variant mt-0.5">
        {{ $leaveType->deducts_balance ? 'Balance tracked' : 'No quota deduction' }}
      </span>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('settings.leave-types.edit', $leaveType) }}"
        class="p-2 rounded-lg hover:bg-surface-container transition-colors active:scale-95">
        <span class="material-symbols-outlined text-primary text-[20px]">edit</span>
      </a>
      <form method="POST" action="{{ route('settings.leave-types.destroy', $leaveType) }}"
        onsubmit="return confirm('Delete {{ $leaveType->name }}? This cannot be undone.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="p-2 rounded-lg hover:bg-red-50 transition-colors active:scale-95">
          <span class="material-symbols-outlined text-danger text-[20px]">delete</span>
        </button>
      </form>
    </div>
  </div>
  @empty
  <div class="bg-white border border-border rounded-xl shadow-sm p-6 text-center">
    <span class="material-symbols-outlined text-on-surface-variant text-[40px]">event_busy</span>
    <p class="font-body-md text-on-surface-variant mt-2">No leave types configured yet.</p>
    <a href="{{ route('settings.leave-types.create') }}"
      class="inline-block mt-3 px-4 py-2 bg-primary text-on-primary font-label-md text-label-md rounded-lg">
      Add First Leave Type
    </a>
  </div>
  @endforelse

  <a href="{{ route('settings.leave-types.create') }}"
    class="block text-center border border-primary text-primary font-label-md text-label-md py-2.5 rounded-xl hover:bg-primary/5 transition-colors mt-1">
    + Add Leave Type
  </a>
</main>
</body></html>
