@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Departments</h4>
    <button class="btn btn-primary btn-sm">
        <i class="bi bi-plus"></i> Add Department
    </button>
</div>

<div class="panel p-3">

    <div class="row g-3">

        <div class="col-md-4">
            <div class="border rounded p-3">
                <h6><i class="bi bi-diagram-3"></i> IT Department</h6>
                <small class="text-muted">12 Employees</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border rounded p-3">
                <h6><i class="bi bi-diagram-3"></i> Finance</h6>
                <small class="text-muted">8 Employees</small>
            </div>
        </div>

    </div>

</div>

@endsection