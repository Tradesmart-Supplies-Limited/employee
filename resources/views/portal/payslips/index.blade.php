@extends('layouts.portal')
@section('title', 'My Payslips')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><i class="bi bi-receipt me-1"></i> My Payslips</h4>
        <small class="text-muted">Payslips appear here once payroll for the period has been finalized.</small>
    </div>
    <form method="GET">
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All years</option>
            @foreach($years as $y)
                <option value="{{ $y }}" @selected((string) request('year') === (string) $y)>{{ $y }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Period</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net pay</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($payslips as $p)
                    <tr>
                        <td><strong>{{ $p->pay_period }}</strong></td>
                        <td class="text-end">K {{ number_format($p->total_income, 2) }}</td>
                        <td class="text-end">K {{ number_format($p->total_deductions, 2) }}</td>
                        <td class="text-end fw-semibold">K {{ number_format($p->net_pay, 2) }}</td>
                        <td>@include('portal.partials.status-badge', ['status' => $p->status])</td>
                        <td class="text-end">
                            <a href="{{ route('portal.payslips.show', $p) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('portal.payslips.pdf', $p) }}" class="btn btn-sm btn-outline-primary" download>
                                <i class="bi bi-download"></i> PDF
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No payslips available yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payslips->hasPages())<div class="card-footer bg-transparent">{{ $payslips->links() }}</div>@endif
</div>

@endsection
