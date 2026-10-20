@extends('emails.leave.workflow.layout')

@php
    $first     = explode(' ', trim($leave->employee_name))[0];
    $stepTitle = $decided ? \App\Models\LeaveRequest::STEPS[$decided]['label'] : 'Approver';
    $nextWho   = $next ? $stepWho($next) : 'the next approver';
@endphp

@section('title', $stepTitle . ' approval recorded')

@section('preheader')
{{ $stepTitle }} approved {{ $leave->employee_name }}'s leave · now with {{ $nextWho }}
@endsection

@section('heading')
    @if($audience === 'employee')
        Your leave has passed {{ $decided === 'hr' ? 'HR' : strtolower($stepTitle) }} approval
    @elseif($myTurn)
        Your approval is needed: {{ $leave->employee_name }}
    @elseif($audience === $decided)
        Your approval has been recorded
    @else
        {{ $leave->employee_name }}'s leave is now with {{ $nextWho }}
    @endif
@endsection

@section('intro')
    @if($audience === 'employee')
        <p style="margin:0 0 10px;">Hi {{ $first }},</p>
        <p style="margin:0;">
            {{ $decidedBy }} has <strong style="color:#15803d;">approved</strong> your {{ $leave->leave_type }} leave.
            It now goes to <strong>{{ $nextWho }}</strong>
            @if($leave->isLastStep($next)) for the <strong>final decision</strong>@else for the next approval @endif.
            We'll email you as soon as it moves on.
        </p>
    @elseif($myTurn)
        <p style="margin:0;">
            {{ $decidedBy }} ({{ $stepTitle }}) has <strong style="color:#15803d;">approved</strong>
            {{ $leave->employee_name }}'s leave.
            <strong>{{ $leave->isLastStep($next) ? 'Your final decision is needed.' : 'Your approval is needed next.' }}</strong>
        </p>
    @elseif($audience === $decided)
        <p style="margin:0;">
            You approved {{ $leave->employee_name }}'s {{ $leave->leave_type }} leave. It has moved on to <strong>{{ $nextWho }}</strong>.
        </p>
    @else
        <p style="margin:0;">
            {{ $decidedBy }} ({{ $stepTitle }}) approved {{ $leave->employee_name }}'s leave. It is now waiting for <strong>{{ $nextWho }}</strong>.
        </p>
    @endif
@endsection

@section('extra')
    @if($decisionNote)
    <tr>
        <td style="padding:14px 28px 0;">
            <div style="border-left:4px solid #16a34a; background:#f8fafc; padding:12px 14px; border-radius:6px;">
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; margin-bottom:4px;">{{ $stepTitle }}'s comments</div>
                <div style="font-size:13px; color:#1f2937; line-height:1.6;">{!! nl2br(e($decisionNote)) !!}</div>
            </div>
        </td>
    </tr>
    @endif
@endsection
