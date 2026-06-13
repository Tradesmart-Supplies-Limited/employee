@extends('layouts.app')

@section('content')

<div class="panel">

    {{-- HEADER --}}
    <div class="panel-header d-flex justify-content-between align-items-center">

        <div>
            <h4 class="mb-0">
                <i class="bi bi-calendar2-week me-2"></i>
                {{ $run->period }} Payroll Run
            </h4>

            <small class="text-muted">
                Status: {{ $run->status }}
            </small>
        </div>

        <div class="d-flex gap-2">

        <!-- Trigger Button -->
            <button type="button"
                    class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#salaryCalculatorModal">
                <i class="bi bi-calculator"></i> Salary Calculator
            </button>

            {{-- GENERATE --}}
            <form action="{{ route('payroll.runs.generate', $run->id) }}" method="POST">
                @csrf
                <button class="btn btn-success btn-sm">
                    <i class="bi bi-cpu"></i>
                    Generate Payroll
                </button>
            </form>

            <button class="btn btn-warning btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#adjustmentModal">
                Add Adjustment
            </button>

            <button class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#payrollRulesModal">

                <i class="bi bi-sliders"></i>
                Payroll Rules
            </button>

            {{-- FINALIZE --}}
            <form action="{{ route('payroll.runs.finalize', $run->id) }}" method="POST">
                @csrf
                <button class="btn btn-primary btn-sm">
                    <i class="bi bi-check2-circle"></i>
                    Finalize Run
                </button>
            </form>

            {{-- PRINT ALL --}}
            <a href="{{ route('payroll.runs.payslips', $run->id) }}"
               target="_blank"
               class="btn btn-dark btn-sm">
                <i class="bi bi-printer"></i>
                Print Payslips
            </a>



        </div>

    </div>

    {{-- SUMMARY CARDS --}}
    <div class="row mt-3">

        <div class="col-md-4">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">Total Employees</small>
                <h4>{{ $run->payrolls->count() }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">Total Deductions</small>
                <h4 class="text-danger">
                    K {{ number_format($run->payrolls->sum('total_deductions'),2) }}
                </h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-3 shadow-sm">
                <small class="text-muted">Net Payroll Cost</small>
                <h4 class="text-success">
                    K {{ number_format($run->payrolls->sum('net_pay'),2) }}
                </h4>
            </div>
        </div>

    </div>

    {{-- TABLE --}}
    <div class="table-responsive mt-4">

        <table class="table table-hover align-middle">

            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Position</th>
                    <th>Basic Salary</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>

            <tbody>

            @forelse($run->payrolls as $payroll)

                <tr class="employee-row"
                        
                        style="cursor:pointer">

                    {{-- EMPLOYEE --}}
                    <td>
                        <div class="fw-semibold">
                            {{ $payroll->employee->first_name }}
                            {{ $payroll->employee->last_name }}
                        </div>

                        <small class="text-muted">
                            {{ $payroll->employee->employee_id }}
                        </small>
                    </td>

                    {{-- POSITION --}}
                    <td>
                        {{ $payroll->employee->position }}
                    </td>

                    {{-- BASIC --}}
                    <td>
                        K {{ number_format($payroll->salary_rate,2) }}
                    </td>

                    {{-- DEDUCTIONS --}}
                    <td class="text-danger fw-semibold">
                        K {{ number_format($payroll->total_deductions,2) }}
                    </td>

                    {{-- NET PAY --}}
                    <td class="text-success fw-bold">
                        K {{ number_format($payroll->net_pay,2) }}
                    </td>

                    {{-- STATUS --}}
                    <td>
                        <span class="badge bg-{{
                            $payroll->status === 'Paid' ? 'success' :
                            ($payroll->status === 'Processed' ? 'primary' :
                            ($payroll->status === 'Generated' ? 'warning' : 'secondary'))
                        }}">
                            {{ $payroll->status }}
                        </span>
                    </td>

                    {{-- ACTION --}}
                    <td class="text-end">

                        <a href="{{ route('payroll.show', $payroll->id) }}"
                           class="btn btn-sm btn-outline-primary">
                            View Payslip
                        </a>

                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        No payroll generated for this run yet.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@push('modals')

@include('dashboard.payroll.runs.partials.modals', ['run' => $run])

{{-- IMPORTANT: RUN ID FOR JS --}}
<script>
    const runId = @json($run->id);
</script>

<script>

    document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.employee-row').forEach(row => {

        row.addEventListener('click', function (e) {

            // don't trigger when clicking View Payslip button
            if (e.target.closest('a,button,form')) {
                return;
            }

            const payrollId = this.dataset.payrollId;

            // select employee in dropdown
            const selector = document.getElementById('employeeSelector');
            selector.value = payrollId;

            // open modal
            const modal = new bootstrap.Modal(
                document.getElementById('adjustmentModal')
            );

            modal.show();

            // trigger existing AJAX loader
            selector.dispatchEvent(new Event('change'));

        });

    });

});


function addItem() {

    const description = document.getElementById('newDesc').value;
    const type = document.getElementById('newType').value;
    const amount = document.getElementById('newAmount').value;

    const payrollId = document.querySelector('[data-payroll-id]').dataset.payrollId;

    fetch(`/payroll/${payrollId}/items/store`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ description, type, amount })
    })
    .then(res => res.json())
    .then(data => {

        if (!data.success) return;

        showToast(data.message, 'success');

        const item = data.item;

        const row = `
        <tr data-id="${item.id}">
            <td>
                <input type="text" value="${item.description}"
                    class="form-control form-control-sm"
                    onblur="updateItem(this, ${item.id}, 'description')">
            </td>

            <td>
                <input type="number" value="${item.amount}"
                    class="form-control form-control-sm text-end"
                    onblur="updateItem(this, ${item.id}, 'amount')">
            </td>

            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteItem(${item.id})">
                    Delete
                </button>
            </td>
        </tr>`;

        const target = type === 'earning'
            ? document.querySelector('#earningsTable')
            : document.querySelector('#deductionsTable');

        if (target) {
            target.insertAdjacentHTML('beforeend', row);
        }

    });

}


function updateItem(input, id, field) {

    console.log('Updating item', { id, field, value: input.value });

    fetch(`/payroll/items/${id}/update-field`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            field: field,
            value: input.value
        })
    })
    .then(res => res.json())
    .then((data) => {
        if(data.success){
            showToast(data.message, 'success');
            console.log(data);
        }
        
    })
    .catch((data) => {
        showToast(data.message, 'error');
        console.log(data);
    });
}





function deleteItem(id) {

console.log('Deleting item with id:', id);

    // if (!confirm('Delete this item?')) return;

    var respo = null;

    fetch(`/payroll/items/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'

        }
    })
    .then(res => res.json())
    .then(data => {

        respo = data;

        if (data.success) {

            // remove row instantly (no reload)
            document.querySelector(`[data-id="${id}"]`)?.remove();
            showToast(data.message, 'success');

        }

        // showToast(data.message, 'error');
    });

    console.log(respo);

}

function refreshOnModalClose(modalId) {
    const modalEl = document.getElementById(modalId);
    if (!modalEl) return;

    modalEl.addEventListener('hidden.bs.modal', function () {
        location.reload();
    });
}

refreshOnModalClose('adjustmentModal');
refreshOnModalClose('payrollRulesModal');


const salaryMode = document.getElementById('salary_mode');
const salaryAmount = document.getElementById('salary_amount');

document.addEventListener('input', calculatePayroll);

function calculatePayroll()
{
    let amount = parseFloat(salaryAmount.value || 0);

    let housing = parseFloat(document.getElementById('housing').value || 0);
    let transport = parseFloat(document.getElementById('transport').value || 0);
    let lunch = parseFloat(document.getElementById('lunch').value || 0);

    let allowances = housing + transport + lunch;

    let basicPay = 0;
    let grossPay = 0;
    let netPay = 0;

    if (salaryMode.value === 'basic')
    {
        basicPay = amount;
        grossPay = basicPay + allowances;
    }

    if (salaryMode.value === 'gross')
    {
        grossPay = amount;
        basicPay = grossPay - allowances;
    }

    if (salaryMode.value === 'net')
    {
        grossPay = reverseGrossFromNet(amount);
        basicPay = grossPay - allowances;
    }

    let napsa = calculateNAPSA(grossPay);
    let nhima = calculateNHIMA(grossPay);

    let taxableIncome = grossPay;

    let paye = calculatePAYE(taxableIncome);

    netPay = grossPay - napsa - nhima - paye;

    document.getElementById('basic_pay').value = formatMoney(basicPay);
    document.getElementById('gross_pay').value = formatMoney(grossPay);
    document.getElementById('net_pay').value = formatMoney(netPay);

    document.getElementById('paye').value = formatMoney(paye);
    document.getElementById('napsa').value = formatMoney(napsa);
    document.getElementById('nhima').value = formatMoney(nhima);
}

function calculateNAPSA(gross)
{
    return gross * 0.05;
}

function calculateNHIMA(gross)
{
    return gross * 0.01;
}

function calculatePAYE(income)
{
    let tax = 0;

    const brackets = [
        { min: 0,    max: 5100, rate: 0 },
        { min: 5100, max: 7100, rate: 0.20 },
        { min: 7100, max: 9200, rate: 0.30 },
        { min: 9200, max: null, rate: 0.37 },
    ];

    for (let i = 0; i < brackets.length; i++) {
        const b = brackets[i];

        if (income <= b.min) continue;

        const upper = b.max === null ? income : b.max;

        const taxable = Math.min(income, upper) - b.min;

        if (taxable > 0) {
            tax += taxable * b.rate;
        }
    }

    return Math.round(tax * 100) / 100;
}

function reverseGrossFromNet(targetNet)
{
    let low = targetNet;
    let high = targetNet * 3;

    while ((high - low) > 0.01)
    {
        let gross = (low + high) / 2;

        let napsa = calculateNAPSA(gross);
        let nhima = calculateNHIMA(gross);

        let taxable = gross - napsa - nhima;

        let paye = calculatePAYE(taxable);

        let net = gross - napsa - nhima - paye;

        if (net < targetNet)
        {
            low = gross;
        }
        else
        {
            high = gross;
        }
    }

    return (low + high) / 2;
}

function formatMoney(value)
{
    return value.toLocaleString('en-ZM', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

</script>

@endpush

@endsection