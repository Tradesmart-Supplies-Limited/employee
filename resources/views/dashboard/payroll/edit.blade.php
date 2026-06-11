@extends('layouts.app')

@section('content')

<div class="panel">

    <div class="panel-header">

        <h4>
            Edit Payroll
        </h4>

    </div>

    <form method="POST"
          action="{{ route('payroll.update',$payroll) }}">

        @csrf
        @method('PUT')

        <div class="row">

            <div class="col-md-4 mb-3">

                <label class="form-label">
                    Salary Rate
                </label>

                <input type="number"
                       step="0.01"
                       name="salary_rate"
                       class="form-control"
                       value="{{ $payroll->salary_rate }}">

            </div>

            <div class="col-md-4 mb-3">

                <label class="form-label">
                    Leave Days
                </label>

                <input type="number"
                       step="0.01"
                       name="leave_days"
                       class="form-control"
                       value="{{ $payroll->leave_days }}">

            </div>

            <div class="col-md-4 mb-3">

                <label class="form-label">
                    Leave Value
                </label>

                <input type="number"
                       step="0.01"
                       name="leave_days_value"
                       class="form-control"
                       value="{{ $payroll->leave_days_value }}">

            </div>

        </div>

        <button class="btn btn-primary">
            Save Changes
        </button>

    </form>

</div>

@endsection