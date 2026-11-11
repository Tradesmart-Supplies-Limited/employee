{{--
    One <li> per approval step in the request's chain (Supervisor → HR → Management).
    Usage inside a .list-group: @include('leave.partials.decision-list', ['leave' => $leave])
--}}
@php $current = $leave->currentStep(); @endphp
@foreach($leave->chain() as $i => $step)
    @php
        $r = $leave->stepRecord($step);
        $meta = \App\Models\LeaveRequest::STEPS[$step];
        $isLast = $leave->isLastStep($step);
    @endphp
    <li class="list-group-item">
        <small class="text-muted d-block">
            {{ $i + 1 }}. {{ $meta['title'] }}@if($isLast) <span class="badge text-bg-light border ms-1">final</span>@endif
        </small>
        @if(in_array($r['decision'], ['Approved', 'Rejected'], true))
            @php $ov = $leave->wasOverridden($step); @endphp
            <span class="{{ $r['decision'] === 'Approved' ? 'text-success' : ($ov ? 'text-warning-emphasis' : 'text-danger') }} fw-semibold">
                <i class="bi {{ $r['decision'] === 'Approved' ? 'bi-check-circle' : ($ov ? 'bi-arrow-repeat' : 'bi-arrow-counterclockwise') }}"></i> {{ \App\Models\LeaveRequest::decisionLabel($r['decision']) }}@if($ov), then overridden by {{ $leave->overridden_by_name }}@endif
            </span>
            <div class="small text-muted">{{ $r['name'] }}@if($r['at']) · {{ \Illuminate\Support\Carbon::parse($r['at'])->format('d M Y') }}@endif</div>
            @if($r['comments'])
                <div class="small mt-1 p-2 bg-body-tertiary rounded">{!! nl2br(e($r['comments'])) !!}</div>
            @endif
        @elseif($r['decision'] === 'Skipped')
            <span class="text-muted"><i class="bi bi-skip-forward"></i> Skipped</span>
            @if($r['comments'])<div class="small text-muted">{{ $r['comments'] }}</div>@endif
        @elseif($leave->isFinal())
            <span class="text-muted"><i class="bi bi-dash-circle"></i> No action required</span>
        @elseif($step === $current)
            <span class="text-warning fw-semibold"><i class="bi bi-hourglass-split"></i> Waiting for approval</span>
            <div class="small text-muted">
                {{ $step === 'supervisor' ? ($leave->assigned_supervisor_name ?? 'Department manager') : $meta['who'] }}
            </div>
        @else
            <span class="text-muted"><i class="bi bi-clock"></i> Waiting for the earlier step</span>
        @endif
    </li>
@endforeach
