@php
    $row = 'padding:9px 12px; border-bottom:1px solid #eef2f7; font-size:13px;';
    $label = $row . ' color:#64748b; width:42%;';
    $value = $row . ' color:#0f172a; font-weight:600;';
    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden;">
    <tr><td colspan="2" style="background:#f1f5f9; padding:9px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#475569;">Application details</td></tr>
    <tr><td style="{{ $label }}">Employee</td><td style="{{ $value }}">{{ $leave->employee_name }}@if($leave->employee_number) <span style="color:#94a3b8; font-weight:400;">· {{ $leave->employee_number }}</span>@endif</td></tr>
    @if($leave->position)
    <tr><td style="{{ $label }}">Position</td><td style="{{ $value }}">{{ $leave->position }}</td></tr>
    @endif
    <tr><td style="{{ $label }}">Leave type</td><td style="{{ $value }}">{{ $leave->leave_type }}@if($leave->other_leave_type) ({{ $leave->other_leave_type }})@endif</td></tr>
    <tr><td style="{{ $label }}">Dates</td><td style="{{ $value }}">{{ $leave->date_from->format('D d M Y') }} &ndash; {{ $leave->date_to->format('D d M Y') }}</td></tr>
    <tr><td style="{{ $label }}">Days requested</td><td style="{{ $value }}">{{ $num($leave->total_days) }}@if($leave->day_rules_label) <span style="color:#94a3b8; font-weight:400;">· {{ $leave->day_rules_label }}</span>@endif</td></tr>
    @if($leave->return_date)
    <tr><td style="{{ $label }}">Returning on</td><td style="{{ $value }}">{{ $leave->return_date->format('D d M Y') }}</td></tr>
    @endif
    <tr><td style="{{ $label }}">Supervisor</td><td style="{{ $value }}">{{ $leave->assigned_supervisor_name ?? 'Not assigned — HR / Admin will review' }}</td></tr>
    @if($leave->reason)
    <tr><td style="{{ $label }}">Reason</td><td style="{{ $row }} color:#0f172a;">{!! nl2br(e($leave->reason)) !!}</td></tr>
    @endif
    @if($leave->status === 'Approved' && $leave->employee_id && (float) $leave->balance_deducted > 0)
    <tr><td style="{{ $label }}">Leave balance</td><td style="{{ $value }}">{{ $num($leave->days_available) }} &rarr; <span style="color:#15803d;">{{ $num($leave->days_balance) }} day(s)</span></td></tr>
    @elseif($leave->employee && ! $leave->isFinal())
    <tr><td style="{{ $label }}">Current balance</td><td style="{{ $value }}">{{ $num($leave->employee->leave_balance) }} day(s)</td></tr>
    @endif
</table>
