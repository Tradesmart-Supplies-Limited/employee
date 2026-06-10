@extends('layouts.leave')

@section('content')

<h3 style="color:#198754;margin-top:0;">Leave Approved</h3>

<p>Dear <strong>{{ $leave->employee_name }}</strong>,</p>

<p>Your leave request has been <strong>approved</strong>.</p>

<hr>

<table style="width:100%;font-size:14px;">
    <tr><td><strong>Leave Type:</strong></td><td>{{ $leave->leave_type }}</td></tr>
    <tr><td><strong>From:</strong></td><td>{{ $leave->date_from }}</td></tr>
    <tr><td><strong>To:</strong></td><td>{{ $leave->date_to }}</td></tr>
    <tr><td><strong>Status:</strong></td><td style="color:green;">Approved</td></tr>
</table>

<br>

<p>We wish you a restful and productive break.</p>

<a href="{{ url('/dashboard') }}"
   style="display:inline-block;background:#198754;color:#fff;padding:10px 15px;border-radius:5px;text-decoration:none;">
   View Dashboard
</a>

@endsection