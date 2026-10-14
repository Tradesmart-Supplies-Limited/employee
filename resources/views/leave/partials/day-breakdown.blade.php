{{-- Which days in the request were not counted. Usage: @include('leave.partials.day-breakdown', ['leave' => $leave]) --}}
@if($leave->day_rules_label)
    <div class="small mt-1" style="line-height:1.7">
        <span class="text-muted">
            {{ $leave->total_days }} leave day(s) of {{ $leave->calendar_days ?? $leave->total_days }} calendar day(s) · {{ $leave->day_rules_label }}
        </span>
        @if(! empty($leave->excluded_dates))
            <br>
            @foreach($leave->excluded_dates as $x)
                @php $isWk = in_array($x['reason'], ['Sunday', 'Saturday'], true); @endphp
                <span style="display:inline-block; padding:.05rem .5rem; margin:.1rem .15rem .1rem 0; border-radius:999px; font-size:.72rem; font-weight:600; {{ $isWk ? 'background:#e2e8f0;color:#334155' : 'background:#fef3c7;color:#92400e' }}">
                    {{ \Carbon\Carbon::parse($x['date'])->format('D d M') }} · {{ $x['reason'] }}
                </span>
            @endforeach
        @endif
    </div>
@endif
