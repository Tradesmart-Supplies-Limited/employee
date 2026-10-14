@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <a href="{{ route('employees.show', $employee) }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> {{ $employee->full_name }}</a>
        <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-pencil-square"></i> Edit {{ $employee->full_name }}</h1>
        <div class="small text-muted">{{ $employee->employee_id }} · jump to any section on the left; <strong>Save changes</strong> saves everything.</div>
    </div>
</div>

@include('dashboard.employees.partials.wizard', ['mode' => 'edit'])

@endsection
