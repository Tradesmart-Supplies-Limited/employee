@extends('emails.leave.workflow.layout')

@php $first = explode(' ', trim($leave->employee_name))[0]; @endphp

@section('title', 'Leave application submitted')

@section('preheader')
{{ $leave->employee_name }} · {{ $leave->leave_type }} leave · {{ $leave->date_from->format('d M') }} – {{ $leave->date_to->format('d M Y') }}
@endsection

@section('heading')
    @if($audience === 'employee')
        Your leave application has been received
    @elseif($audience === 'supervisor')
        {{ $leave->employee_name }} needs your review
    @else
        New leave application from {{ $leave->employee_name }}
    @endif
@endsection

@section('intro')
    @if($audience === 'employee')
        <p style="margin:0 0 10px;">Hi {{ $first }},</p>
        <p style="margin:0;">
            Thanks. Your <strong>{{ $leave->leave_type }}</strong> leave request is in.
            @if($leave->assigned_supervisor_name)
                It goes to <strong>{{ $leave->assigned_supervisor_name }}</strong> for review first, then to HR for final approval.
            @else
                HR will review it and make the final decision.
            @endif
            We'll email you at every step.
        </p>
    @elseif($audience === 'supervisor')
        <p style="margin:0 0 10px;">Hello,</p>
        <p style="margin:0;">
            <strong>{{ $leave->employee_name }}</strong> reports to you and has applied for
            <strong>{{ rtrim(rtrim(number_format((float) $leave->total_days, 1), '0'), '.') }} day(s)</strong> of {{ $leave->leave_type }} leave.
            Please <strong>recommend approval or rejection</strong>. HR makes the final decision after your review.
        </p>
    @else
        <p style="margin:0;">
            A new leave application was submitted.
            @if($leave->assigned_supervisor_name)
                It is with <strong>{{ $leave->assigned_supervisor_name }}</strong> for supervisor review. You'll be notified when it's ready for your final decision, or you can act on it now.
            @else
                <strong>No supervisor is assigned to this employee</strong>, so it's waiting on HR / Admin directly.
            @endif
        </p>
    @endif
@endsection

@section('footnote')
    @if($audience === 'employee')
        You can withdraw the request from the self-service portal while it is still pending.
    @elseif($audience === 'supervisor' && ! $actionUrl)
        You don't have a portal login yet. Please ask HR to record your decision, or to create your login.
    @endif
@endsection
