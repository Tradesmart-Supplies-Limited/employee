@extends('layouts.app')

@section('content')

<div class="panel">

    <div class="panel-header">
        <h4>Create Payroll Run</h4>
    </div>

    <form method="POST" action="{{ route('payroll.runs.store') }}">
        @csrf

        <label>Payroll Period</label>

        <input type="month"
               name="period"
               class="form-control mb-3"
               required>

        <button class="btn btn-primary">
            Create Run
        </button>

    </form>

</div>

@endsection