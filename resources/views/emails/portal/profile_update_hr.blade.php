@extends('emails.layouts.brand')

@php use App\Support\EmployeeFields as F; @endphp

@section('title', 'Employee details updated')
@section('tag', 'Self-service')
@section('preheader', $employee->full_name . ' · ' . ($event === 'document' ? 'new documents' : 'details updated'))

@section('heading')
    @if($event === 'approval_needed')
        {{ $employee->full_name }} asked to change their details
    @elseif($event === 'document')
        {{ $employee->full_name }} uploaded {{ count($documents) }} document(s)
    @else
        {{ $employee->full_name }} updated their details
    @endif
@endsection

@section('body')
    <p style="margin:0 0 14px;">
        {{ $employee->employee_id }} · {{ $employee->position ?? '—' }}{{ $employee->department ? ' · ' . $employee->department : '' }}
    </p>

    @if($event === 'approval_needed')
        <p style="margin:0 0 14px;"><strong>These changes are on hold until HR approves them.</strong> Please verify them against supporting documents (e.g. a new NRC or a bank letter) before approving.</p>
    @endif

    @if($changes)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb; border-radius:10px; border-collapse:separate; overflow:hidden;">
            <tr>
                <td style="background:#f1f5f9; padding:8px 12px; font-size:11px; font-weight:700; text-transform:uppercase; color:#475569;">Field</td>
                <td style="background:#f1f5f9; padding:8px 12px; font-size:11px; font-weight:700; text-transform:uppercase; color:#475569;">Was</td>
                <td style="background:#f1f5f9; padding:8px 12px; font-size:11px; font-weight:700; text-transform:uppercase; color:#475569;">Now</td>
            </tr>
            @foreach($changes as $field => $c)
                @php $mask = ! empty(F::get($field)['mask']); @endphp
                <tr>
                    <td style="padding:8px 12px; border-top:1px solid #f1f5f9; font-size:13px; color:#64748b;">{{ F::label($field) }}</td>
                    <td style="padding:8px 12px; border-top:1px solid #f1f5f9; font-size:13px; color:#94a3b8; text-decoration:line-through;">{{ $mask ? F::mask($c['old']) : ($c['old'] ?? '—') }}</td>
                    <td style="padding:8px 12px; border-top:1px solid #f1f5f9; font-size:13px; color:#0f172a; font-weight:700;">{{ $mask ? F::mask($c['new']) : ($c['new'] ?? '—') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($documents)
        <ul style="margin:0; padding-left:18px; font-size:13px;">
            @foreach($documents as $d)<li><strong>{{ $d['name'] }}</strong> <span style="color:#64748b;">({{ $d['category'] }})</span></li>@endforeach
        </ul>
    @endif

    @if($note)
        <div style="margin-top:14px; border-left:4px solid #185FA5; background:#f8fafc; padding:10px 14px; border-radius:6px; font-size:13px;">
            <strong>Employee's note:</strong> {{ $note }}
        </div>
    @endif

    @include('emails.layouts.partials.button', ['url' => $event === 'approval_needed' ? $reviewUrl : $profileUrl, 'label' => $event === 'approval_needed' ? 'Review & approve' : 'Open employee profile'])
@endsection

@section('footnote')
    Sensitive numbers are partly hidden in this email. The full values are in the HR system.
@endsection
