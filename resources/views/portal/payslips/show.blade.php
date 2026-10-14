@extends('layouts.portal')
@section('title', 'Payslip ' . $payroll->pay_period)

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1">Payslip &ndash; {{ $payroll->pay_period }}</h4>
        <small class="text-muted">{{ $payroll->employee->full_name }} &middot; EIN {{ $payroll->employee->employee_id }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('portal.payslips.pdf', $payroll) }}" class="btn btn-primary btn-sm" download>
            <i class="bi bi-download"></i> Download PDF
        </a>
        <a href="{{ route('portal.payslips.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body">
        <small class="text-muted">Total earnings</small>
        <h4 class="mb-0">K {{ number_format($payroll->total_income, 2) }}</h4>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0"><div class="card-body">
        <small class="text-muted">Total deductions</small>
        <h4 class="mb-0">K {{ number_format($payroll->total_deductions, 2) }}</h4>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm border-0 border-start border-success border-3"><div class="card-body">
        <small class="text-muted">Net pay</small>
        <h4 class="mb-0 text-success">K {{ number_format($payroll->net_pay, 2) }}</h4>
    </div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent"><strong>Earnings</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($earnings as $i)
                            <tr>
                                <td>{{ $i->description }}</td>
                                <td class="text-end">K {{ number_format($i->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-muted">No earnings recorded.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light"><tr><th>Total</th><th class="text-end">K {{ number_format($payroll->total_income, 2) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent"><strong>Deductions</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($deductions as $i)
                            <tr>
                                <td>{{ $i->description }}</td>
                                <td class="text-end">K {{ number_format($i->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-muted">No deductions recorded.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light"><tr><th>Total</th><th class="text-end">K {{ number_format($payroll->total_deductions, 2) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
