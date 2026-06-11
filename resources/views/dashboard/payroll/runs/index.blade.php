@extends('layouts.app')

@section('content')

<div class="panel">

    <div class="panel-header d-flex justify-content-between">

        <div>
            <h4>Payroll Runs</h4>
            <small>Monthly payroll processing cycles</small>
        </div>

        <a href="{{ route('payroll.runs.create') }}"
           class="btn btn-primary">
            New Run
        </a>

    </div>

    <div class="row">

        @foreach($runs as $run)

        <div class="col-md-4 mb-3">

            <div class="card p-3">

                <h5>{{ $run->period }}</h5>

                <span class="badge bg-info">
                    {{ $run->status }}
                </span>

                <hr>

                <p>Employees: {{ $run->payrolls->count() }}</p>

                <p>Net Pay: K {{ number_format($run->net_pay,2) }}</p>

                <div class="d-flex gap-2">

                    <a href="{{ route('payroll.runs.show', $run->id) }}"
                       class="btn btn-sm btn-outline-primary w-100">
                        Open
                    </a>

                    <a href="{{ route('payroll.runs.generate', $run->id) }}"
                       class="btn btn-sm btn-outline-success w-100">
                        Generate
                    </a>

                </div>

            </div>

        </div>

        @endforeach

    </div>

</div>

@endsection