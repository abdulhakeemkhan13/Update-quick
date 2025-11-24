@props(['routeKey'])

@php
  // expects $reportDescriptions to be defined in the parent view
  $desc = $reportDescriptions[$routeKey] ?? '';
@endphp

@if($desc !== '')
  <i class="bi bi-question-circle report-help"
     role="button"
     tabindex="0"
     aria-label="What is this report?"
     data-desc="{{ e($desc) }}"></i>
@endif
