@extends('layouts.leave')

@section('content')

<h3 style="margin-top:0;">Leave Application Received</h3>

<p>Dear <strong>{{ $leave->employee_name }}</strong>,</p>

<p>Your leave application has been successfully submitted and is now pending review.</p>

<hr>

<table style="width:100%;font-size:14px;">
    <tr><td><strong>Employee No:</strong></td><td>{{ $leave->employee_no }}</td></tr>
    <tr><td><strong>Leave Type:</strong></td><td>{{ $leave->leave_type }}</td></tr>
    <tr><td><strong>From:</strong></td><td>{{ $leave->date_from }}</td></tr>
    <tr><td><strong>To:</strong></td><td>{{ $leave->date_to }}</td></tr>
    <tr><td><strong>Total Days:</strong></td><td>{{ $leave->total_days }}</td></tr>
    <tr><td><strong>Status:</strong></td><td><span style="color:#f0ad4e;">Pending</span></td></tr>
</table>

<br>

<p>
You will receive an update once your supervisor reviews the request.
</p>

<a href="{{ url('/dashboard') }}"
   style="display:inline-block;background:#0d6efd;color:#fff;padding:10px 15px;border-radius:5px;text-decoration:none;">
   View Dashboard
</a>

@endsection