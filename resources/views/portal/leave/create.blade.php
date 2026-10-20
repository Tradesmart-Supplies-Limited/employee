@extends('layouts.portal')
@section('title', 'Apply for Leave')

@section('content')

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
    <div>
        <a href="{{ route('portal.leave.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left"></i> My leave</a>
        <h4 class="mb-0 mt-1"><i class="bi bi-calendar-plus me-1"></i> Apply for Leave</h4>
        <small class="text-muted">Four quick steps. Your details are filled in from your employee record.</small>
    </div>
</div>

@include('leave.partials.apply-form')

@endsection
