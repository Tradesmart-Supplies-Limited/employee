@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">

        <div>
            <h2 class="mb-1">
                <i class="ti ti-wallet me-2"></i>
                Create Payroll Run
            </h2>

            <p class="text-muted mb-0">
                Start a new payroll cycle for your organization.
            </p>
        </div>

        <a href="{{ route('payroll.runs.index') }}"
           class="btn btn-light">
            <i class="ti ti-arrow-left me-1"></i>
            Back to Payroll Runs
        </a>

    </div>

    {{-- Payroll Process --}}
    <div class="row mb-4">

        <div class="col-md-3">
            <div class="card border-primary shadow-sm">
                <div class="card-body text-center">
                    <div class="fw-bold text-primary">1</div>
                    <small>Create Run</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted">
                    <div class="fw-bold">2</div>
                    <small>Generate Payroll</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted">
                    <div class="fw-bold">3</div>
                    <small>Review & Adjust</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted">
                    <div class="fw-bold">4</div>
                    <small>Approve & Export</small>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        {{-- Form --}}
        <div class="col-lg-7">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        Payroll Details
                    </h5>
                </div>

                <div class="card-body">

                    <form method="POST"
                          action="{{ route('payroll.runs.store') }}">
                        @csrf

                        {{-- Payroll Period --}}
                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Payroll Period
                            </label>

                            <input type="month"
                                   name="period"
                                   id="period"
                                   class="form-control form-control-lg"
                                   required>

                            <div class="form-text">
                                Select the payroll month.
                            </div>

                        </div>

                        {{-- Alias --}}
                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Payroll Run Alias
                            </label>

                            <input type="text"
                                   name="alias"
                                   id="alias"
                                   class="form-control form-control-lg"
                                   placeholder="July 2026 Main Payroll">

                            <div class="form-text">
                                Optional. Useful when running multiple payrolls
                                in the same month.
                            </div>

                        </div>

                        <button class="btn btn-primary btn-lg w-100">

                            <i class="ti ti-plus me-2"></i>

                            Create Payroll Run

                        </button>

                    </form>

                </div>

            </div>

        </div>

        {{-- Preview --}}
        <div class="col-lg-5">

            <div class="card shadow-sm border-0">

                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        Payroll Preview
                    </h5>
                </div>

                <div class="card-body">

                    <div class="mb-3">
                        <small class="text-muted">Period</small>

                        <div class="fw-semibold"
                             id="previewPeriod">
                            Not Selected
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Alias</small>

                        <div class="fw-semibold"
                             id="previewAlias">
                            Not Specified
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Status</small>

                        <div>
                            <span class="badge bg-warning">
                                Draft
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Created By</small>

                        <div class="fw-semibold">
                            {{ auth()->user()->name }}
                        </div>
                    </div>

                    <hr>

                    <div class="alert alert-info mb-0">

                        <strong>Next Step:</strong>

                        After creating the payroll run, you will be able to:

                        <ul class="mb-0 mt-2">
                            <li>Generate employee payrolls</li>
                            <li>Add overtime & adjustments</li>
                            <li>Review deductions</li>
                            <li>Approve payroll</li>
                            <li>Export payslips</li>
                        </ul>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>

const periodInput = document.getElementById('period');
const aliasInput  = document.getElementById('alias');

const previewPeriod = document.getElementById('previewPeriod');
const previewAlias  = document.getElementById('previewAlias');

periodInput.addEventListener('change', function () {

    if (!this.value) return;

    const date = new Date(this.value + '-01');

    const monthName = date.toLocaleString('default', {
        month: 'long',
        year: 'numeric'
    });

    previewPeriod.textContent = monthName;

    if (aliasInput.value.trim() === '') {
        aliasInput.value = monthName + ' Main Payroll';
        previewAlias.textContent = aliasInput.value;
    }
});

aliasInput.addEventListener('input', function () {

    previewAlias.textContent =
        this.value.trim() || 'Not Specified';

});

</script>
@endpush