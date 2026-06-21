


<div class="offcanvas offcanvas-start" tabindex="-1" id="salaryCanvas" style="width: 420px;">

    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Salary Calculator</h5>
        <button class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

     <div class="offcanvas-body">

                <!-- Calculation Mode -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Calculation Mode</label>
                        <select id="salary_mode" class="form-select">
                            <option value="basic">Basic Pay</option>
                            <option value="gross">Gross Pay</option>
                            <option value="net">Target Net Pay</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount (ZMW)</label>
                        <input type="number"
                               class="form-control"
                               id="salary_amount"
                               value="8500">
                    </div>
                </div>

                <hr>

                <!-- Allowances -->
                <h6 class="fw-bold mb-3">Allowances</h6>

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>Housing Allowance</label>
                        <input type="number"
                               class="form-control allowance"
                               id="housing"
                               value="0">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Transport Allowance</label>
                        <input type="number"
                               class="form-control allowance"
                               id="transport"
                               value="0">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Lunch Allowance</label>
                        <input type="number"
                               class="form-control allowance"
                               id="lunch"
                               value="0">
                    </div>

                </div>

                <hr>

                <!-- Salary Summary -->
                <h6 class="fw-bold mb-3">Salary Summary</h6>

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>Basic Pay</label>
                        <input type="text"
                               class="form-control bg-light"
                               id="basic_pay"
                               readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Gross Pay</label>
                        <input type="text"
                               class="form-control bg-light"
                               id="gross_pay"
                               readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Net Pay</label>
                        <input type="text"
                               class="form-control bg-light"
                               id="net_pay"
                               readonly>
                    </div>

                </div>

                <hr>

                <!-- Statutory Deductions -->
                <h6 class="fw-bold mb-3">Statutory Deductions</h6>

                <div class="row">

                    <div class="col-md-4 mb-3">
                        <label>PAYE</label>
                        <input type="text"
                               class="form-control bg-light"
                               id="paye"
                               readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>NAPSA</label>
                        <input type="text"
                               class="form-control bg-light"
                               id="napsa"
                               readonly>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>NHIMA</label>
                        <input type="text"
                               class="form-control bg-light"
                               id="nhima"
                               readonly>
                    </div>

                </div>

                <hr>

                <!-- Payslip Preview -->
                <div class="alert alert-info mb-0">
                    <strong>Preview:</strong>
                    The values above are estimates based on the selected salary calculation mode and current deduction rates.
                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Close
                </button>

                <button type="button"
                        class="btn btn-success"
                        onclick="calculatePayroll()">
                    Recalculate
                </button>

            </div>
</div>


<div class="offcanvas offcanvas-end" tabindex="-1" id="overtimeCanvas" style="width: 420px;">

    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Overtime Calculator</h5>
        <button class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">

        <label class="form-label">Hourly Rate</label>
        <input type="number" class="form-control mb-2" id="otRate">

        <label class="form-label">Normal OT Hours (1.5x)</label>
        <input type="number" class="form-control mb-2" id="otNormal">

        <label class="form-label">Double OT Hours (2x)</label>
        <input type="number" class="form-control mb-2" id="otDouble">

        <button class="btn btn-success w-100" onclick="calculateOT()">
            Calculate
        </button>

        <hr>

        <div id="otResult" class="fw-bold text-primary"></div>

    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="leaveCanvas" style="width: 420px;">

    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Leave Calculator</h5>
        <button class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">

        <label class="form-label">Annual Leave Days</label>
        <input type="number" class="form-control mb-2" id="leaveAnnual">

        <label class="form-label">Days Taken</label>
        <input type="number" class="form-control mb-2" id="leaveTaken">

        <button class="btn btn-warning w-100" onclick="calculateLeave()">
            Calculate
        </button>

        <hr>

        <div id="leaveResult" class="fw-bold text-warning"></div>

    </div>
</div>


<div class="offcanvas offcanvas-end" tabindex="-1" id="toolsOffcanvas">

    <div class="offcanvas-header">
        <h5 class="offcanvas-title">
            <i class="bi bi-tools me-2"></i> Payroll Tools
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">

        <p class="text-muted small">Quick calculators & utilities</p>

       <div class="d-grid gap-2">

    <button
        class="btn btn-outline-primary text-start"
        data-bs-dismiss="offcanvas"
        data-bs-toggle="offcanvas"
        data-bs-target="#salaryCanvas">

        <i class="bi bi-cash-stack me-2"></i>
        Salary Calculator

    </button>

    <button
        class="btn btn-outline-success text-start"
        data-bs-dismiss="offcanvas"
        data-bs-toggle="offcanvas"
        data-bs-target="#overtimeCanvas">

        <i class="bi bi-clock-history me-2"></i>
        Overtime Calculator

    </button>

    <button
        class="btn btn-outline-warning text-start"
        data-bs-dismiss="offcanvas"
        data-bs-toggle="offcanvas"
        data-bs-target="#leaveCanvas">

        <i class="bi bi-calendar-check me-2"></i>
        Leave Days Calculator

    </button>

</div>

    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="adjustmentModalBulk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content shadow-lg border-0 rounded-4">

            <!-- Header -->
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-excel"></i>
                    Payroll Adjustments Import
                </h5>

                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">

                <form
                    method="POST"
                    action="{{ route('payroll.runs.adjustments.import', $run) }}"
                    enctype="multipart/form-data"
                    id="adjustmentForm">

                    @csrf

                    <!-- Adjustment Type -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-sliders"></i> Adjustment Type
                        </label>

                        <select
                            id="rule_id"
                            name="rule_id"
                            class="form-select"
                            required>

                            @foreach($rules as $rule)
                                <option value="{{ $rule->id }}">
                                    {{ $rule->code }} - {{ $rule->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <!-- Download Template -->
                    <div class="mb-3 d-flex justify-content-between align-items-center">

                        <div>
                            <label class="form-label fw-semibold">
                                <i class="bi bi-download"></i> Template
                            </label>

                            <p class="text-muted small mb-0">
                                Download Excel with employees prefilled
                            </p>
                        </div>

                        <button
                            type="button"
                            id="download-template"
                            class="btn btn-success">

                            <i class="bi bi-file-earmark-arrow-down"></i>
                            Download

                        </button>

                    </div>

                    <!-- File Upload -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-file-earmark-arrow-up"></i> Upload Excel File
                        </label>

                        <input
                            type="file"
                            name="file"
                            class="form-control"
                            accept=".xlsx,.xls"
                            required>
                    </div>

                </form>

            </div>

            <!-- Footer -->
            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-light"
                    data-bs-dismiss="modal">

                    <i class="bi bi-x-circle"></i> Cancel

                </button>

                <button
                    type="submit"
                    form="adjustmentForm"
                    class="btn btn-primary">

                    <i class="bi bi-cloud-upload"></i> Import

                </button>

            </div>

        </div>

    </div>
</div>
<script>
document.getElementById('download-template').addEventListener('click', function () {

    let ruleId = document.getElementById('rule_id').value;

    window.location =
        "{{ url('/payroll/runs/'.$run->id.'/adjustments/template') }}/" + ruleId;
});
</script>