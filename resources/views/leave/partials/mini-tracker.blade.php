{{-- Compact progress bar + stage label. Usage: @include('leave.partials.mini-tracker', ['leave' => $leave]) --}}
@include('leave.partials.tracker-styles')
@php $steps = $leave->timeline(); @endphp
<div class="lt-mini" title="{{ collect($steps)->map(fn ($s) => $s['title'] . ': ' . $s['note'])->implode(' · ') }}">
    <div class="lt-mini-bar">
        @foreach($steps as $step)
            <span class="lt-mini-seg is-{{ $step['state'] }}"></span>
        @endforeach
    </div>
    <span class="lt-mini-label text-{{ $leave->stage_color }}">
        <i class="bi {{ $leave->stage_icon }}"></i> {{ $leave->stage_label }}
    </span>
</div>
