@extends('emails.layouts.brand')

@php
    $status = $change?->status;
    $stage = match ($event) { 'decided' => $status, 'withdrawn' => 'withdrawn', default => $pending ? 'review' : null };
@endphp

@section('title', 'Employee detail change')
@section('tag', 'Self-service · HR')
@section('preheader', $employee->full_name . ' · ' . ($event === 'submitted' ? ($pending ? 'approval needed' : 'details updated') : $event))

@section('heading')
    @if($event === 'decided')
        {{ $employee->full_name }}'s change was {{ $status }}
    @elseif($event === 'withdrawn')
        {{ $employee->full_name }} withdrew their change request
    @elseif($pending)
        {{ $employee->full_name }} asked to change their details
    @else
        {{ $employee->full_name }} updated their details
    @endif
@endsection

@section('body')
    <p style="margin:0 0 12px; color:#64748b;">{{ $employee->employee_id }} · {{ $employee->position ?? '—' }}{{ $employee->department ? ' · ' . $employee->department : '' }}</p>

    @if($stage)@include('emails.portal.partials.change-tracker', ['stage' => $stage])@endif

    @if($event === 'submitted' && $pending)
        <p style="margin:0 0 14px;"><strong>The changes under "Waiting for your approval" are on hold.</strong> Check them against a supporting document (new NRC, bank letter, marriage certificate…) before approving. The employee has been told HR is reviewing.</p>
    @elseif($event === 'decided')
        <p style="margin:0 0 14px;">{{ $actor ?? 'HR' }} {{ $status }} this request {{ $change?->reviewed_at ? 'on ' . $change->reviewed_at->format('d M Y H:i') : '' }}. The employee has been emailed. This copy is for the HR team's records.</p>
    @elseif($event === 'withdrawn')
        <p style="margin:0 0 14px;">Nothing needs doing: the record was not changed.</p>
    @else
        <p style="margin:0 0 14px;">These were updated on the employee's record straight away (they don't need approval). This email is for your records.</p>
    @endif

    @if($pending)@include('emails.portal.partials.change-table', ['rows' => $pending, 'title' => $event === 'decided' ? 'Requested changes' : 'Waiting for your approval', 'color' => $event === 'decided' ? ($status === 'approved' ? '#16a34a' : '#dc2626') : '#d97706'])@endif
    @if($applied)@include('emails.portal.partials.change-table', ['rows' => $applied, 'title' => 'Updated straight away', 'color' => '#185FA5'])@endif

    @if($note)
        <div style="margin:0 0 6px; border-left:4px solid #185FA5; background:#f8fafc; padding:10px 14px; border-radius:6px; font-size:13px;"><strong>Employee's note:</strong> {{ $note }}</div>
    @endif
    @if($event === 'decided' && $change?->review_note)
        <div style="margin:0 0 6px; border-left:4px solid {{ $status === 'approved' ? '#16a34a' : '#dc2626' }}; background:#f8fafc; padding:10px 14px; border-radius:6px; font-size:13px;"><strong>HR's note:</strong> {{ $change->review_note }}</div>
    @endif

    @include('emails.layouts.partials.button', ['url' => $event === 'submitted' && $pending ? $reviewUrl : $profileUrl, 'label' => $event === 'submitted' && $pending ? 'Review & approve' : 'Open employee profile'])
@endsection

@section('footnote')
    Sensitive numbers are partly hidden in this email. The full values are in the HR system under Employees → Detail Changes.
@endsection
