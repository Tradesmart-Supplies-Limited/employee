@extends('layouts.app')

@section('content')

<div class="panel">

    {{-- HEADER --}}
{{-- HEADER --}}
<div class="panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">

    {{-- LEFT --}}
    <div>
        <h4 class="mb-0">
            <i class="bi bi-calendar2-week me-2"></i>
            {{ $run->period }} - {{$run->alias }}
        </h4>

        <small class="text-muted">
            Status: <span class="fw-semibold">{{ $run->status }}</span>
        </small>
    </div>

    {{-- RIGHT ACTION BAR --}}
    <div class="d-flex align-items-center flex-wrap gap-2">
        

    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#adjustmentModal"><i class="bi bi-sliders"></i> Add Adjustment </button> 
    {{-- LOANS --}}
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#adjustmentModalBulk">
            <i class="bi bi-upload"></i> Bulk Adjustments
        </button>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#payrollRulesModal"> <i class="bi bi-sliders"></i> Payroll Rules </button>

        

        {{-- GENERATE --}}
        <form action="{{ route('payroll.runs.generate', $run->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-tools me-1"></i> Generate Payroll
            </button>
        </form>

        

        


        {{-- TOOLS --}}
        <button class="btn btn-ouline-secondary btn-sm"
                data-bs-toggle="offcanvas"
                data-bs-target="#toolsOffcanvas"
                title="Tools">
            <i class="bi bi-tools me-1"></i> Tools
        </button>

        {{-- PRINT --}}
        <a href="{{ route('payroll.runs.payslips', $run->id) }}"
           target="_blank"
           class="btn btn-outline-secondary btn-sm"
           data-bs-toggle="tooltip"
           title="Print Payslips">
            <i class="bi bi-printer me-1"></i> Print
        </a>


        <form action="{{ route('payroll.email.bulk', $run) }}"
            method="POST"
            onsubmit="return confirmBulkEmail(this)">
            @csrf
            <button type="submit" class="btn btn-dark btn-sm" id="emailBulkBtn">
                <i class="bi bi-envelope-paper"></i>
                <span id="emailBulkBtnText">Email all payslips</span>
            </button>
        </form>

        
        {{-- SUBMIT --}}
        <button class="btn btn-outline-secondary btn-sm"
                data-bs-toggle="tooltip"
                title="Submit Payroll">
            <i class="bi bi-send-check me-1"></i> Submit
        </button>

        {{-- FINALIZE --}}

        @if(auth()->check() && auth()->user()->role === 'admin')
            <form action="{{ route('payroll.runs.finalize', $run->id) }}" method="POST">
                @csrf
                <button class="btn btn-success btn-sm"
                        data-bs-toggle="tooltip"
                        title="Finalize Run">
                    <i class="bi bi-check2-circle me-1"></i> Finalize
                </button>
            </form>
        @endif

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

                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                            <a href="{{ route('payroll.show', $payroll->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>
                            </a>

                            <a href="{{ route('payroll.pdf', $payroll) }}"
                               class="btn btn-sm btn-outline-success">
                                <i class="bi bi-download me-1"></i>
                            </a>

                            <form action="{{ route('payroll.email.single', $payroll) }}"
                                method="POST"
                                onsubmit="return confirmSingleEmail(this)">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" id="emailSingleBtn">
                                    <i class="bi bi-share me-1"></i>
                                </button>
                            </form>
                        </div>

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
@include('dashboard.payroll.runs.partials.payroll-rules-modal')
@include('dashboard.payroll.runs.partials.adjustment-modal')

{{-- IMPORTANT: RUN ID FOR JS --}}
<script>
    const runId = @json($run->id);
</script>

<script>
function confirmSingleEmail(form) {
    const email = "{{ $payroll->employee->personal_email ?? '' }}";
 
    if (!email) {
        alert('This employee has no email address on file. Add one before sending.');
        return false;
    }
 
    if (!confirm(`Send this payslip to ${email}?`)) {
        return false;
    }
 
    const btn = document.getElementById('emailSingleBtn');
    const text = document.getElementById('emailSingleBtnText');
    btn.disabled = true;
    text.textContent = 'Sending…';
 
    return true;
}
</script>

<script>
function confirmBulkEmail(form) {
    const employeeCount = {{ $run->payrolls->count() }};
 
    if (!confirm(`Send payslips to all ${employeeCount} employees in this run? This cannot be undone.`)) {
        return false;
    }
 
    const btn = document.getElementById('emailBulkBtn');
    const text = document.getElementById('emailBulkBtnText');
    btn.disabled = true;
    text.textContent = 'Queuing emails…';
 
    return true;
}
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

/*
|--------------------------------------------------------------------------
| TOGGLE TAX PROFILE visibility based on earning/deduction
|--------------------------------------------------------------------------
*/
function toggleTaxProfile() {
    const type  = document.getElementById('adjType').value;
    const group = document.getElementById('taxProfileGroup');
    group.classList.toggle('d-none', type === 'deduction');
}

// Run once on load so it matches the default select value
// toggleTaxProfile();

/*
|--------------------------------------------------------------------------
| SUBMIT ADJUSTMENT → POST to storeAdjustment() → reload summary
|--------------------------------------------------------------------------
*/
function submitAdjustment() {
    

    const name       = document.getElementById('adjName').value.trim();
    const type       = document.getElementById('adjType').value;
    const taxProfile = document.getElementById('adjTaxProfile').value;
    const ruleId = document.getElementById('adjRuleId').value;
    const amount     = document.getElementById('adjAmount').value;
    const errorBox   = document.getElementById('adjError');

    // console.log("Rule ID : ", ruleId);

    // Client-side guard
    errorBox.classList.add('d-none');
    errorBox.textContent = '';

    if (!name)   { showAdjError('Description is required.');    return; }
    if (!amount || parseFloat(amount) <= 0) {
                   showAdjError('Enter a valid amount.');        return; }

    // Read context from parent div written by Blade
    const ctx        = document.querySelector('[data-payroll-id]');
    const runId      = ctx.dataset.runId;
    const employeeId = ctx.dataset.employeeId;

    // Disable button, show spinner
    setAdjLoading(true);

   fetch(`/payroll/runs/${runId}/adjustments`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            employee_id: employeeId,
            name: name,
            type: type,
            formula_type: 'fixed',
            value: amount,
            tax_profile: type === 'earning' ? taxProfile : null,
            rule_id: ruleId
        }),
    })
    .then(async (res) => {

        const text = await res.text(); // ALWAYS safe first

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('NOT JSON RESPONSE:', text);
            throw new Error('Server did not return JSON');
        }

        if (!res.ok) {
            console.error('SERVER ERROR:', data);
            showAdjError(data.message ?? 'Server error');
            return;
        }

        setAdjLoading(false);

        showToast('Adjustment saved — payslip recalculated.', 'success');

        document.getElementById('adjName').value = '';
        document.getElementById('adjAmount').value = '';

        reloadEmployeeSummary(document.getElementById('employeeSelector').value);
    })
    .catch((err) => {
        setAdjLoading(false);
        console.error(err);
        showAdjError(err.message ?? 'Request failed');
    });
}

/*
|--------------------------------------------------------------------------
| DELETE ADJUSTMENT → DELETE → reload summary
|--------------------------------------------------------------------------
*/
function deleteAdjustment(adjId) {

    if (!confirm('Remove this adjustment and recalculate the payslip?')) return;

    fetch(`/payroll/runs/adjustments/${adjId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            showToast(data.message ?? 'Could not remove adjustment.', 'error');
            return;
        }

        showToast('Adjustment removed — payslip recalculated.', 'success');
        reloadEmployeeSummary(document.getElementById('employeeSelector').value);
    });
}

/*
|--------------------------------------------------------------------------
| UPDATE ITEM FIELD (inline edit — description only for non-statutory items)
|--------------------------------------------------------------------------
*/
function updateItemField(input, itemId, field) {
    fetch(`/payroll/items/${itemId}/update-field`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ field, value: input.value }),
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message ?? 'Update failed.', 'error');
        }
    });
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function showAdjError(msg) {
    const box = document.getElementById('adjError');
    box.textContent = msg;
    box.classList.remove('d-none');
}

function setAdjLoading(loading) {
    document.getElementById('adjSubmitBtn').disabled  = loading;
    document.getElementById('adjBtnText').classList.toggle('d-none', loading);
    document.getElementById('adjBtnSpinner').classList.toggle('d-none', !loading);
}




/*
|--------------------------------------------------------------------------
| RELOAD EMPLOYEE SUMMARY
| Called on:
|   - employee selector change
|   - after storeAdjustment succeeds
|   - after destroyAdjustment succeeds
|--------------------------------------------------------------------------
*/
function reloadEmployeeSummary(payrollId) {

    if (!payrollId) {
        document.getElementById('employeePayrollSummary').innerHTML =
            '<div class="text-center text-muted py-5">Select an employee to view their payslip.</div>';
        return;
    }

    const container = document.getElementById('employeePayrollSummary');

    // Show skeleton while loading
    container.innerHTML = `
        <div class="text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            Loading payslip…
        </div>`;

    const runId = {{ $run->id }};

    fetch(`/payroll/runs/${runId}/employee/${payrollId}/summary`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.text();
    })
    .then(html => {
        container.innerHTML = html;
        // Re-run toggleTaxProfile() defined in the partial so the
        // tax profile field reflects the current select value on load
        if (typeof toggleTaxProfile === 'function') toggleTaxProfile();
    })
    .catch(err => {
        container.innerHTML =
            `<div class="alert alert-danger">Failed to load payslip. ${err.message}</div>`;
    });
}

/*
|--------------------------------------------------------------------------
| TOAST HELPER (simple — swap for your own if you have one)
|--------------------------------------------------------------------------
*/
function showToast(message, type = 'success') {
    // If you're using Bootstrap toasts, wire this up to your toast component.
    // Fallback: console + alert so nothing silently fails.
    const colour = type === 'success' ? '#198754' : '#dc3545';
    console.log(`[${type.toUpperCase()}] ${message}`);

    // Simple floating toast
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 m-3 p-3 rounded text-white shadow';
    toast.style.cssText    = `background:${colour};z-index:9999;min-width:220px`;
    toast.textContent      = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
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



(function () {

    /* ── State ────────────────────────────────────────────────── */
    let esMode         = 'rule';
    let esSelectedRule = null;

    /* ── Mode switcher ────────────────────────────────────────── */
    window.esSetMode = function (mode) {
        esMode = mode;
        document.getElementById('esModeRule').classList.toggle('active',   mode === 'rule');
        document.getElementById('esModeCustom').classList.toggle('active', mode === 'custom');
        document.getElementById('esRuleSection').style.display = mode === 'rule' ? '' : 'none';

        if (mode === 'custom') {
            esClearRule();
            esMakeFormEditable();
        }
    };

    /* ── Rule card selection ──────────────────────────────────── */
    window.esSelectRule = function (el) {
        document.querySelectorAll('#esRuleGrid .es-rule-card')
                .forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');

        const d = el.dataset;
        esSelectedRule = { id: d.ruleId, name: d.name, type: d.type, tax: d.tax };

        document.getElementById('adjRuleId').value    = d.ruleId;
        document.getElementById('adjRuleId').readOnly  = true;


        document.getElementById('adjName').value    = d.name;
        document.getElementById('adjName').readOnly = true;

        document.getElementById('adjType').value    = d.type;
        document.getElementById('adjType').disabled = true;

        document.getElementById('adjTaxProfile').value    = d.tax;
        document.getElementById('adjTaxProfile').disabled = true;

        if (d.value) {
            document.getElementById('adjAmount').value = parseFloat(d.value).toFixed(2);
        }

        document.getElementById('taxProfileGroup').style.visibility =
            d.type === 'earning' ? 'visible' : 'hidden';

        const banner = document.getElementById('esRuleBanner');
        banner.style.display = 'flex';
        document.getElementById('esRuleBannerText').textContent = d.name + ' selected';

        document.getElementById('adjAmount').focus();
    };

    /* ── Clear rule selection ─────────────────────────────────── */
    window.esClearRule = function () {
        esSelectedRule = null;
        document.querySelectorAll('#esRuleGrid .es-rule-card')
                .forEach(c => c.classList.remove('selected'));
        document.getElementById('esRuleBanner').style.display = 'none';
        document.getElementById('adjName').value   = '';
        document.getElementById('adjAmount').value = '';
        esMakeFormEditable();
    };

    /* ── Unlock all form fields ───────────────────────────────── */
    function esMakeFormEditable() {
        document.getElementById('adjName').readOnly       = false;
        document.getElementById('adjType').disabled       = false;
        document.getElementById('adjTaxProfile').disabled = false;
        document.getElementById('taxProfileGroup').style.visibility = 'visible';
    }

    /* ── Toggle tax profile visibility ───────────────────────── */
    window.toggleTaxProfile = function () {
        const type = document.getElementById('adjType').value;
        document.getElementById('taxProfileGroup').style.visibility =
            type === 'earning' ? 'visible' : 'hidden';
    };

})();



</script>



@endpush

@endsection