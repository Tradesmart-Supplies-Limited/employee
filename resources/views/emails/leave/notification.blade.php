@extends('layouts.leave')

@section('content')

<h3>New Leave Application</h3>

<p>A new leave request has been submitted for review.</p>

<hr>

<table style="width:100%;font-size:14px;">
    <tr><td><strong>Name:</strong></td><td>{{ $leave->employee_name }}</td></tr>
    <tr><td><strong>Email:</strong></td><td>{{ $leave->employee_email }}</td></tr>
    <tr><td><strong>Leave Type:</strong></td><td>{{ $leave->leave_type }}</td></tr>
    <tr><td><strong>From:</strong></td><td>{{ $leave->date_from }}</td></tr>
    <tr><td><strong>To:</strong></td><td>{{ $leave->date_to }}</td></tr>
    <tr><td><strong>Total Days:</strong></td><td>{{ $leave->total_days }}</td></tr>
</table>

<br>

<a href="{{ url('/dashboard') }}"
   style="display:inline-block;background:#0d6efd;color:#fff;padding:10px 15px;border-radius:5px;text-decoration:none;">
   Review Request
</a>

@endsection