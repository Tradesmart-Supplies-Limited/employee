@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <a href="{{ route('employees.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> Employees</a>
        <h1 class="h4 fw-bold mb-0 mt-1"><i class="bi bi-person-plus"></i> Add an employee</h1>
        <div class="small text-muted">Five short steps. Only name and position are required; everything else can be added later.</div>
    </div>
    <a href="{{ route('employees.import') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cloud-upload"></i> Adding many? Import a spreadsheet</a>
</div>

@include('dashboard.employees.partials.wizard', ['mode' => 'create'])

@endsection
