{{-- Full 4-step stage tracker. Usage: @include('leave.partials.tracker', ['leave' => $leave]) --}}
@include('leave.partials.tracker-styles')
@php
    $icons = ['done' => 'bi-check-lg', 'declined' => 'bi-x-lg', 'skipped' => 'bi-skip-forward', 'cancelled' => 'bi-dash-lg'];
@endphp
<div class="lt" role="list" aria-label="Leave application progress">
    @foreach($leave->timeline() as $step)
        <div class="lt-step is-{{ $step['state'] }}" role="listitem"
             aria-current="{{ $step['state'] === 'current' ? 'step' : 'false' }}">
            <div class="lt-dot"><i class="bi {{ $icons[$step['state']] ?? $step['icon'] }}"></i></div>
            <div class="lt-title">{{ $step['title'] }}</div>
            <div class="lt-note">{{ $step['note'] }}</div>
            @if($step['who'])<div class="lt-who">{{ $step['who'] }}</div>@endif
            @if($step['when'])<div class="lt-when">{{ $step['when']->format('d M Y, H:i') }}</div>@endif
        </div>
    @endforeach
</div>
