@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between mb-3">

    <h4>
        Payroll Details
    </h4>

    <div>

        <a href="{{ route('payroll.edit',$payroll) }}"
           class="btn btn-secondary">

            Edit

        </a>

        <button onclick="window.print()"
                class="btn btn-primary">

            Print Payslip

        </button>

    </div>

</div>

@include('dashboard.payroll.payslip')

@endsection