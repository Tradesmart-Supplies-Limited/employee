@extends('layouts.leave')

@section('content')

<h2>Leave Pending HR Approval</h2>

<p>A leave request has been approved by the supervisor and is awaiting HR final approval.</p>

<ul>
    <li><strong>Name:</strong> {{ $leave->employee_name }}</li>
    <li><strong>Position:</strong> {{ $leave->position }}</li>
    <li><strong>Dates:</strong> {{ $leave->date_from }} → {{ $leave->date_to }}</li>
    <li><strong>Days:</strong> {{ $leave->total_days }}</li>
</ul>

<p>
<a href="{{ url('/leave/'.$leave->id) }}">Open Leave Request</a>
</p>

@endsection