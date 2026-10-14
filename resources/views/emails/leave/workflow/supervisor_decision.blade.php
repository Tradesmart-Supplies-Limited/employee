@extends('emails.leave.workflow.layout')

@php
    $first       = explode(' ', trim($leave->employee_name))[0];
    $recommended = $leave->supervisor_decision === 'Approved';
@endphp

@section('title', 'Supervisor review complete')

@section('preheader')
Supervisor {{ $recommended ? 'recommended' : 'did not recommend' }} {{ $leave->employee_name }}'s leave · awaiting HR
@endsection

@section('heading')
    @if($audience === 'employee')
        Your supervisor has reviewed your leave
    @elseif($audience === 'supervisor')
        Your review has been recorded
    @else
        Ready for final approval: {{ $leave->employee_name }}
    @endif
@endsection

@section('intro')
    @if($audience === 'employee')
        <p style="margin:0 0 10px;">Hi {{ $first }},</p>
        <p style="margin:0;">
            {{ $leave->supervisor_name }} has
            <strong style="color:{{ $recommended ? '#15803d' : '#b91c1c' }};">{{ $recommended ? 'recommended your leave for approval' : 'not recommended your leave' }}</strong>.
            Your request now goes to HR for the <strong>final decision</strong>. We'll email you as soon as it's made.
        </p>
    @elseif($audience === 'supervisor')
        <p style="margin:0;">
            You <strong>{{ $recommended ? 'recommended approval of' : 'did not recommend' }}</strong> {{ $leave->employee_name }}'s
            {{ $leave->leave_type }} leave. HR has been notified and will make the final decision.
        </p>
    @else
        <p style="margin:0;">
            {{ $leave->supervisor_name }} has
            <strong style="color:{{ $recommended ? '#15803d' : '#b91c1c' }};">{{ $recommended ? 'recommended approval' : 'recommended rejection' }}</strong>
            of {{ $leave->employee_name }}'s leave. <strong>Your final decision is needed.</strong>
        </p>
    @endif
@endsection

@section('extra')
    @if($leave->supervisor_comments)
    <tr>
        <td style="padding:14px 28px 0;">
            <div style="border-left:4px solid {{ $recommended ? '#16a34a' : '#dc2626' }}; background:#f8fafc; padding:12px 14px; border-radius:6px;">
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; margin-bottom:4px;">Supervisor's comments</div>
                <div style="font-size:13px; color:#1f2937; line-height:1.6;">{!! nl2br(e($leave->supervisor_comments)) !!}</div>
            </div>
        </td>
    </tr>
    @endif
@endsection
