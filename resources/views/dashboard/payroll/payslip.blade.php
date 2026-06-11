@extends('layouts.app')

@section('content')

<div class="container py-4">

    <!-- ACTION BUTTONS -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h4 class="mb-0">Employee Payslip</h4>
            <small class="text-muted">
                Period: {{ $payroll->pay_period }}
            </small>
        </div>

        <div class="d-flex gap-2">

            <a href="{{ route('payroll.print', $payroll) }}"
               target="_blank"
               class="btn btn-dark btn-sm">
                <i class="bi bi-printer"></i> Print
            </a>

            <a href="{{ route('payroll.index') }}"
               class="btn btn-secondary btn-sm">
                Back
            </a>

        </div>

    </div>

    <!-- PAYSLIP CARD -->
    <div class="card shadow-sm">

        <div class="card-body">

            <!-- HEADER -->
            <div class="text-center border-bottom pb-3 mb-3">

                <h5 class="fw-bold mb-0">
                    TRADESMART SUPPLIES LIMITED
                </h5>

                <small class="text-muted">
                    Official Employee Payslip
                </small>

            </div>

            <!-- EMPLOYEE INFO -->
            <div class="row mb-4">

                <div class="col-md-6">
                    <p><strong>Employee:</strong>
                        {{ $payroll->employee->first_name }}
                        {{ $payroll->employee->last_name }}
                    </p>

                    <p><strong>Employee ID:</strong>
                        {{ $payroll->employee->employee_id }}
                    </p>

                    <p><strong>Position:</strong>
                        {{ $payroll->employee->position }}
                    </p>
                </div>

                <div class="col-md-6 text-md-end">

                    <p><strong>Branch:</strong>
                        {{ $payroll->branch }}
                    </p>

                    <p><strong>Department:</strong>
                        {{ $payroll->cost_centre }}
                    </p>

                    <p><strong>Pay Period:</strong>
                        {{ $payroll->pay_period }}
                    </p>

                </div>

            </div>

            <!-- PAY BREAKDOWN -->
            <div class="row">

                <!-- EARNINGS -->
                <div class="col-md-6">

                    <h6 class="fw-bold text-success">Earnings</h6>

                    <table class="table table-sm">

                        <tbody>

                        @foreach($payroll->items->where('type','earning') as $item)

                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-end">
                                    K {{ number_format($item->amount,2) }}
                                </td>
                            </tr>

                        @endforeach

                        </tbody>

                    </table>

                </div>

                <!-- DEDUCTIONS -->
                <div class="col-md-6">

                    <h6 class="fw-bold text-danger">Deductions</h6>

                    <table class="table table-sm">

                        <tbody>

                        @foreach($payroll->items->where('type','deduction') as $item)

                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-end">
                                    K {{ number_format($item->amount,2) }}
                                </td>
                            </tr>

                        @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

            <!-- TOTALS -->
            <hr>

            <div class="row">

                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Earnings</strong></p>
                    <h5 class="text-success">
                        K {{ number_format($payroll->total_income,2) }}
                    </h5>
                </div>

                <div class="col-md-4">
                    <p class="mb-1"><strong>Total Deductions</strong></p>
                    <h5 class="text-danger">
                        K {{ number_format($payroll->total_deductions,2) }}
                    </h5>
                </div>

                <div class="col-md-4 text-md-end">
                    <p class="mb-1"><strong>Net Pay</strong></p>
                    <h4 class="text-primary fw-bold">
                        K {{ number_format($payroll->net_pay,2) }}
                    </h4>
                </div>

            </div>

            <!-- FOOTER -->
            <hr>

            <div class="text-center text-muted small">

                <p class="mb-1">
                    This is a computer generated payslip and does not require a signature.
                </p>

                <p class="mb-0">
                    Generated on {{ now()->format('d M Y H:i') }}
                </p>

            </div>

        </div>

    </div>

</div>

@endsection