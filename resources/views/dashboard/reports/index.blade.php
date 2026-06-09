@extends('layouts.app')

@section('content')

<h4 class="mb-3"><i class="bi bi-bar-chart"></i> Reports</h4>

<div class="panel p-3">

    <div class="row g-3">

        <div class="col-md-4">
            <div class="border rounded p-3">
                <i class="bi bi-people"></i>
                <h6>Employee Report</h6>
            </div>
        </div>

        <div class="col-md-4">
            <div class="border rounded p-3">
                <i class="bi bi-calendar-check"></i>
                <h6>Leave Report</h6>
            </div>
        </div>

    </div>

</div>

@endsection