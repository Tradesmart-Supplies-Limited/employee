@extends('emails.leave.workflow.layout')

@php
    $first    = explode(' ', trim($leave->employee_name))[0];
    $approved = $leave->status === 'Approved';
    $num      = fn ($n) => rtrim(rtrim(number_format((float) $n, 1), '0'), '.');
@endphp

@section('title', 'Leave ' . strtolower($leave->status))

@section('preheader')
{{ $leave->employee_name }}'s {{ $leave->leave_type }} leave has been {{ strtolower($leave->status) }}
@endsection

@section('heading')
    @if($audience === 'employee')
        {!! $approved ? 'Your leave is approved &#127881;' : 'Your leave request was not approved' !!}
    @else
        {{ $leave->employee_name }}'s leave was {{ strtolower($leave->status) }}
    @endif
@endsection

@section('intro')
    @if($audience === 'employee')
        <p style="margin:0 0 10px;">Hi {{ $first }},</p>
        @if($approved)
            <p style="margin:0;">
                {{ $leave->hr_name }} has given final approval for your <strong>{{ $leave->leave_type }}</strong> leave from
                <strong>{{ $leave->date_from->format('d M') }}</strong> to <strong>{{ $leave->date_to->format('d M Y') }}</strong>.
                @if($leave->return_date) You're expected back on <strong>{{ $leave->return_date->format('l, d M Y') }}</strong>. @endif
            </p>
        @else
            <p style="margin:0;">
                {{ $leave->hr_name }} has declined your {{ $leave->leave_type }} leave request. The reason is below.
                Your leave balance has not changed. Please talk to your supervisor or HR if you'd like to discuss it or apply for different dates.
            </p>
        @endif
    @else
        <p style="margin:0;">
            Final decision by <strong>{{ $leave->hr_name }}</strong>:
            <strong style="color:{{ $approved ? '#15803d' : '#b91c1c' }};">{{ $leave->status }}</strong>.
            @if($approved && $leave->employee_id && (float) $leave->balance_deducted > 0)
                {{ $num($leave->balance_deducted) }} day(s) were deducted; {{ $first }} has {{ $num($leave->days_balance) }} day(s) remaining.
            @endif
        </p>
    @endif
@endsection

@section('extra')
    @if($approved && $leave->employee_id && $audience === 'employee')
    <tr>
        <td style="padding:14px 28px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ecfdf3; border:1px solid #bbf7d0; border-radius:10px;">
                <tr>
                    <td align="center" style="padding:14px;">
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#15803d;">Balance before</div>
                        <div style="font-size:22px; font-weight:800; color:#0f172a;">{{ $num($leave->days_available) }}</div>
                    </td>
                    <td align="center" style="padding:14px;">
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#15803d;">Deducted</div>
                        <div style="font-size:22px; font-weight:800; color:#b91c1c;">&minus;{{ $num($leave->balance_deducted) }}</div>
                    </td>
                    <td align="center" style="padding:14px;">
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#15803d;">Remaining</div>
                        <div style="font-size:22px; font-weight:800; color:#15803d;">{{ $num($leave->days_balance) }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endif

    @if($leave->hr_comments)
    <tr>
        <td style="padding:14px 28px 0;">
            <div style="border-left:4px solid {{ $approved ? '#16a34a' : '#dc2626' }}; background:#f8fafc; padding:12px 14px; border-radius:6px;">
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; margin-bottom:4px;">HR comments</div>
                <div style="font-size:13px; color:#1f2937; line-height:1.6;">{!! nl2br(e($leave->hr_comments)) !!}</div>
            </div>
        </td>
    </tr>
    @endif
@endsection
