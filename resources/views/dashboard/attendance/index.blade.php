@extends('layouts.app')

@section('content')

<h4 class="mb-3"><i class="bi bi-clock-history"></i> Attendance Module</h4>

<div class="panel p-3">

    <h5 class="mb-3">In Progress modules</h5>
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title">QRcode based Attendance management system</h6>
                    <p class="card-text text-muted">Integration coming soon for QR code attendance tracking and management.</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title">Import from Biometric Attendance machine</h6>
                    <p class="card-text text-muted">Future support for importing attendance data from biometric devices.</p>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection