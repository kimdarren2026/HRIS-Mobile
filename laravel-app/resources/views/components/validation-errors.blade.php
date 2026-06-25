@props(['variant'])

@if($variant === 'settings')
@if($errors->any())
<div class="mb-4 bg-red-50 border border-red-200 text-error rounded-lg px-4 py-3 text-body-md">
  @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
</div>
@endif
@elseif($variant === 'finance')
@if($errors->any())
  <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 font-body-md text-body-md">
    <ul class="list-disc pl-4 space-y-1">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
@endif
