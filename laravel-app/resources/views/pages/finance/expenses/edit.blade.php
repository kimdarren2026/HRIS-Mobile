<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Edit Expense - HRIS Mobile App</title>
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
  <a href="{{ route('finance.expenses.show', $expense) }}" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary">Edit Expense</h1>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  <x-validation-errors variant="finance" />

  <form method="POST" action="{{ route('finance.expenses.update', $expense) }}" enctype="multipart/form-data" class="flex flex-col gap-unit-md">
    @csrf
    @method('PUT')

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Category *</label>
      <select name="category" required class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
        @foreach(\App\Models\CompanyExpense::CATEGORIES as $cat)
          <option value="{{ $cat }}" {{ old('category', $expense->category) === $cat ? 'selected' : '' }}>{{ str_replace('_', ' ', $cat) }}</option>
        @endforeach
      </select>
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Title *</label>
      <input type="text" name="title" value="{{ old('title', $expense->title) }}" maxlength="255" required
        class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Amount (Rp) *</label>
      <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" min="0.01" step="0.01" required
        class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Expense Date *</label>
      <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required
        class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Recipient / Vendor *</label>
      <input type="text" name="recipient_name" value="{{ old('recipient_name', $expense->recipient_name) }}" maxlength="255" required
        class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Linked Employee (optional)</label>
      <select name="employee_id" class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
        <option value="">None</option>
        @foreach($employees as $emp)
          <option value="{{ $emp->id }}" {{ old('employee_id', $expense->employee_id) == $emp->id ? 'selected' : '' }}>
            {{ $emp->user?->name ?? $emp->nik }}
          </option>
        @endforeach
      </select>
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Cost Center / Department</label>
      <input type="text" name="cost_center" value="{{ old('cost_center', $expense->cost_center) }}" maxlength="100"
        class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">Description</label>
      <textarea name="description" rows="3" maxlength="2000"
        class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary resize-none">{{ old('description', $expense->description) }}</textarea>
    </div>

    <div class="flex flex-col gap-unit-xs">
      <label class="font-label-md text-label-md text-on-surface-variant">
        Replace Receipt (JPG/PNG/PDF, max 5MB)
        @if($expense->receipt_path)
          <span class="text-success ml-1">— existing receipt on file</span>
        @endif
      </label>
      <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"
        class="border border-border rounded-xl px-4 py-3 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <button type="submit" class="w-full bg-primary text-white py-3.5 rounded-xl font-label-md text-label-md flex items-center justify-center gap-2 active:opacity-90 mt-unit-sm">
      <span class="material-symbols-outlined">save</span>
      Save Changes
    </button>
  </form>

</main>

</body></html>
