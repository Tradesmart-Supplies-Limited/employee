@extends('emails.leave.workflow.layout')

@php
    $first = explode(' ', trim($leave->employee_name))[0];
@endphp

@section('title', 'New comment on a leave request')

@section('preheader')
{{ $comment?->author_name }} commented on {{ $audience === 'employee' ? 'your' : $leave->employee_name . "'s" }} leave request
@endsection

@section('heading')
    {{ $comment?->author_name ?? 'Someone' }} added a comment
@endsection

@section('intro')
    <p style="margin:0 0 10px;">{{ $audience === 'employee' ? 'Hi ' . $first . ',' : 'Hello,' }}</p>
    <p style="margin:0;">
        There's a new comment on {{ $audience === 'employee' ? 'your' : $leave->employee_name . "'s" }}
        <strong>{{ $leave->leave_type }}</strong> leave request ({{ $leave->date_from->format('d M') }} – {{ $leave->date_to->format('d M Y') }}),
        which is currently <strong>{{ strtolower($leave->stage_label) }}</strong>.
        You're welcome to read it and reply on the request.
    </p>
@endsection

@section('extra')
    @if($comment)
    <tr>
        <td style="padding:14px 28px 0;">
            <div style="border-left:4px solid #185FA5; background:#f8fafc; padding:12px 14px; border-radius:6px;">
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; margin-bottom:4px;">{{ $comment->author_name }} · {{ $comment->author_role }}</div>
                <div style="font-size:13px; color:#1f2937; line-height:1.6;">{!! nl2br(e($comment->body)) !!}</div>
            </div>
        </td>
    </tr>
    @endif
@endsection
