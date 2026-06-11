@extends('layouts.app')

@section('content')

<div class="panel">

    <!-- HEADER -->
    <div class="panel-header d-flex justify-content-between align-items-center">

        <div>
            <h4 class="mb-0">
                <i class="bi bi-cash-stack me-2"></i>
                Payroll Management
            </h4>
            <small class="text-muted">
                Payroll engine records & processing history
            </small>
        </div>

        <div>

            <a href="{{ route('payroll.runs.index') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i>
            Payroll Runs
        </a>

        <a href="{{ route('payroll.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i>
            Create Single Payroll
        </a>
        </div>

        

       

    </div>

    <!-- TABLE -->
    <div class="table-responsive">

        <table class="table table-hover align-middle">

            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Period</th>
                    <th>Branch</th>
                    <th>Income</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>

            @forelse($payrolls as $payroll)

                <tr>

                    <!-- EMPLOYEE -->
                    <td>
                        <div class="fw-semibold">
                            {{ $payroll->employee->first_name }}
                            {{ $payroll->employee->last_name }}
                        </div>
                        <small class="text-muted">
                            {{ $payroll->employee->employee_id }}
                        </small>
                    </td>

                    <!-- PERIOD -->
                    <td>
                        <span class="badge bg-light text-dark">
                            {{ $payroll->pay_period }}
                        </span>
                    </td>

                    <!-- BRANCH -->
                    <td>{{ $payroll->branch }}</td>

                    <!-- INCOME -->
                    <td class="text-success fw-semibold">
                        K {{ number_format($payroll->total_income ?? 0, 2) }}
                    </td>

                    <!-- DEDUCTIONS -->
                    <td class="text-danger fw-semibold">
                        K {{ number_format($payroll->total_deductions ?? 0, 2) }}
                    </td>

                    <!-- NET PAY -->
                    <td class="fw-bold">
                        K {{ number_format($payroll->net_pay ?? 0, 2) }}
                    </td>

                    <!-- STATUS -->
                    <td>
                        <span class="badge bg-{{
                            $payroll->status === 'Paid' ? 'success' :
                            ($payroll->status === 'Processed' ? 'primary' :
                            ($payroll->status === 'Draft' ? 'warning' : 'secondary'))
                        }}">
                            {{ $payroll->status }}
                        </span>
                    </td>

                    <!-- ACTIONS -->
                    <td class="text-end">

                        <div class="btn-group btn-group-sm">

                            <a href="{{ route('payroll.show', $payroll) }}"
                               class="btn btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="{{ route('payroll.edit', $payroll) }}"
                               class="btn btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <a href="{{ route('payroll.print', $payroll) }}"
                               target="_blank"
                               class="btn btn-outline-dark">
                                <i class="bi bi-printer"></i>
                            </a>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        No payroll records found. Create your first payroll run.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection