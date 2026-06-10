@extends('layouts.leave')

@section('content')

<h3 style="color:#dc3545;margin-top:0;">Leave Rejected</h3>

<p>Dear <strong>{{ $leave->employee_name }}</strong>,</p>

<p>Unfortunately, your leave request has been declined.</p>

<hr>

<table style="width:100%;font-size:14px;">
    <tr><td><strong>Leave Type:</strong></td><td>{{ $leave->leave_type }}</td></tr>
    <tr><td><strong>Status:</strong></td><td style="color:red;">Rejected</td></tr>
</table>

@if($leave->admin_comment)
<br>
<p><strong>Reason:</strong></p>
<p style="background:#f8d7da;padding:10px;border-radius:5px;">
    {{ $leave->admin_comment }}
</p>
@endif

<br>

<a href="{{ url('/dashboard') }}"
   style="display:inline-block;background:#dc3545;color:#fff;padding:10px 15px;border-radius:5px;text-decoration:none;">
   View Dashboard
</a>

@endsection