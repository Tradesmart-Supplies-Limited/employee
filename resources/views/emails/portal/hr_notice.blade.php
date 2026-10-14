@extends('emails.layouts.brand')

@section('title', 'Portal account ' . $verb)
@section('tag', 'HR notification')
@section('preheader', $employee->full_name . ' — portal account ' . $verb . ' by ' . $actorName)

@section('heading')
    Portal account {{ $verb }}: {{ $employee->full_name }}
@endsection

@section('body')
    @php
        $row   = 'padding:9px 12px; border-bottom:1px solid #eef2f7; font-size:13px;';
        $label = $row . ' color:#64748b; width:40%;';
        $value = $row . ' color:#0f172a; font-weight:600;';
        $text  = [
            'created' => 'A new Employee Self-Service login was created.',
            'reset'   => 'A new temporary password was issued for this employee\'s portal login.',
            'removed' => 'This employee\'s portal access was removed. Their employee record is unchanged.',
        ][$event] ?? '';
    @endphp

    <p style="margin:0 0 16px;">{{ $text }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden;">
        <tr><td style="{{ $label }}">Employee</td><td style="{{ $value }}">{{ $employee->full_name }} <span style="color:#94a3b8; font-weight:400;">· {{ $employee->employee_id }}</span></td></tr>
        <tr><td style="{{ $label }}">Position / Department</td><td style="{{ $value }}">{{ $employee->position ?? '—' }}{{ $employee->department ? ' · ' . $employee->department : '' }}</td></tr>
        <tr><td style="{{ $label }}">Login email</td><td style="{{ $value }}">{{ $loginEmail }}</td></tr>
        <tr><td style="{{ $label }}">Reports to</td><td style="{{ $value }}">{{ $employee->supervisor_name ?? 'Not assigned' }}</td></tr>
        <tr><td style="{{ $label }}">Done by</td><td style="{{ $value }}">{{ $actorName }}</td></tr>
        <tr><td style="{{ $label }}">When</td><td style="{{ $value }}">{{ $when->format('D d M Y, H:i') }}</td></tr>
        @if($event !== 'removed')
            <tr>
                <td style="{{ $label }}">Login details email</td>
                <td style="{{ $row }} font-weight:600; color:{{ $emailDelivered ? '#15803d' : '#b91c1c' }};">
                    {{ $emailDelivered ? '✓ Sent to the employee' : '✗ Could not be sent. Share the temporary password manually or try again' }}
                </td>
            </tr>
        @endif
    </table>

    @include('emails.layouts.partials.button', ['url' => $profileUrl, 'label' => 'Open employee profile'])
@endsection

@section('footnote')
    For security, passwords are never included in HR notifications.
@endsection
