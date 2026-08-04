@props(['value' => null, 'limit' => 60])

{{--
    Phase 60B — safe renderer for free-text attendance columns (out-of-radius
    reason, work plan, work result, approval note) inside the discipline table.

    Long values are collapsed behind a native <details> so a row stays readable
    without needing JavaScript or an edit endpoint. Both the summary and the full
    body print through {{ }}, so employee-supplied markup is escaped and never
    executes. A NULL value — normal for records predating Phase 60A — renders as
    an em dash rather than an empty cell or a "0".
--}}
@php
    $text = is_string($value) ? trim($value) : null;
@endphp

@if($text === null || $text === '')
  <span class="text-on-surface-variant">—</span>
@elseif(mb_strlen($text) <= $limit)
  <span class="whitespace-pre-line break-words">{{ $text }}</span>
@else
  <details class="group">
    <summary class="cursor-pointer list-none text-primary">
      <span class="whitespace-pre-line break-words">{{ mb_substr($text, 0, $limit) }}…</span>
      <span class="font-label-sm text-label-sm underline ml-1 group-open:hidden">Selengkapnya</span>
    </summary>
    <span class="whitespace-pre-line break-words block mt-1">{{ $text }}</span>
  </details>
@endif
