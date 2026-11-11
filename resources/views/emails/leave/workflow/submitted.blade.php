@extends('emails.leave.workflow.layout')

@php
    $first = explode(' ', trim($leave->employee_name))[0];
    $days  = rtrim(rtrim(number_format((float) $leave->total_days, 1), '0'), '.');
@endphp

@section('title', 'Leave application submitted')

@section('preheader')
{{ $leave->employee_name }} · {{ $leave->leave_type }} leave · {{ $leave->date_from->format('d M') }} – {{ $leave->date_to->format('d M Y') }}
@endsection

@section('heading')
    @if($audience === 'employee')
        Your leave application has been received
    @elseif($myTurn)
        {{ $leave->employee_name }} needs your approval
    @else
        New leave application from {{ $leave->employee_name }}
    @endif
@endsection

@section('intro')
    @if($audience === 'employee')
        <p style="margin:0 0 10px;">Hi {{ $first }},</p>
        <p style="margin:0;">
            Thanks. Your <strong>{{ $leave->leave_type }}</strong> leave request is in.
            It will be approved by <strong>{{ $leave->chain_label }}</strong>, in that order.
            @if($next === 'supervisor' && $leave->assigned_supervisor_name)
                It's with <strong>{{ $leave->assigned_supervisor_name }}</strong> first.
            @endif
            We'll email you at every step.
        </p>
    @elseif($myTurn)
        <p style="margin:0 0 10px;">Hello,</p>
        <p style="margin:0;">
            <strong>{{ $leave->employee_name }}</strong> has applied for
            <strong>{{ $days }} day(s)</strong> of {{ $leave->leave_type }} leave.
            Could you kindly <strong>approve or revert</strong> it?
            @if($leave->isLastStep($next))
                Yours is the final decision.
            @else
                After you, it goes to {{ $stepWho($leave->nextStepAfter($next)) }}.
            @endif
        </p>
    @else
        <p style="margin:0;">
            A new leave application was submitted. Approval chain: <strong>{{ $leave->chain_label }}</strong>.
            It is now with <strong>{{ $next === 'supervisor' ? ($leave->assigned_supervisor_name ?? 'the department manager') : $stepWho($next) }}</strong>.
            You'll be told as it moves along.
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
