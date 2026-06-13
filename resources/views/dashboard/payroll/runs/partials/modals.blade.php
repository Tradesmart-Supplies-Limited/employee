<div class="modal fade"
     id="adjustmentModal"
     tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">
                    Payroll Adjustment Manager
                </h5>

                <button class="btn-close"
                        data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">

                {{-- EMPLOYEE --}}
                <div class="mb-3">

                    <label class="form-label">
                        Employee
                    </label>

                    <select id="employeeSelector"
                            class="form-select">

                        <option value="">
                            Select Employee
                        </option>

                        @foreach($run->payrolls as $payroll)

                            <option value="{{ $payroll->id }}">
                                {{ $payroll->employee->employee_id }}
                                -
                                {{ $payroll->employee->first_name }}
                                {{ $payroll->employee->last_name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                {{-- AJAX CONTENT --}}
                <div id="employeePayrollSummary">

                    <div class="text-center text-muted py-5">

                        Select employee to view payslip.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<div class="modal fade" id="payrollRulesModal" tabindex="-1">

    <div class="modal-dialog modal-xl">

        <div class="modal-content">

            {{-- HEADER --}}
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-sliders"></i>
                    Payroll Rules Manager
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- RULE LIST + EDIT --}}
                <form action="{{ route('payroll.rules.bulkUpdate') }}" method="POST">
                    @csrf

                    <table class="table table-sm align-middle">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Formula</th>
                                <th>Value</th>
                                <th>Active</th>
                                <th>Delete</th>
                            </tr>
                        </thead>

                        <tbody>

                        @foreach(\App\Models\PayrollRule::all() as $rule)

                            <tr>

                                {{-- NAME --}}
                                <td>
                                    <input type="text"
                                           name="rules[{{ $rule->id }}][name]"
                                           value="{{ $rule->name }}"
                                           class="form-control form-control-sm">
                                </td>

                                {{-- TYPE --}}
                                <td>
                                    <select name="rules[{{ $rule->id }}][type]"
                                            class="form-select form-select-sm">

                                        <option value="earning" {{ $rule->type == 'earning' ? 'selected' : '' }}>
                                            Earning
                                        </option>

                                        <option value="deduction" {{ $rule->type == 'deduction' ? 'selected' : '' }}>
                                            Deduction
                                        </option>

                                    </select>
                                </td>

                                {{-- FORMULA --}}
                                <td>
                                    <select name="rules[{{ $rule->id }}][formula_type]"
                                            class="form-select form-select-sm">

                                        <option value="fixed" {{ $rule->formula_type == 'fixed' ? 'selected' : '' }}>
                                            Fixed
                                        </option>

                                        <option value="percentage" {{ $rule->formula_type == 'percentage' ? 'selected' : '' }}>
                                            %
                                        </option>

                                    </select>
                                </td>

                                {{-- VALUE --}}
                                <td>
                                    <input type="number"
                                           step="0.01"
                                           name="rules[{{ $rule->id }}][value]"
                                           value="{{ $rule->value }}"
                                           class="form-control form-control-sm">
                                </td>

                                {{-- ACTIVE --}}
                                <td>
                                    <input type="checkbox"
                                           name="rules[{{ $rule->id }}][active]"
                                           value="1"
                                           {{ $rule->active ? 'checked' : '' }}>
                                </td>

                                {{-- DELETE --}}
                                <td>
                                    <input type="checkbox"
                                           name="rules[{{ $rule->id }}][delete]"
                                           value="1">
                                </td>

                            </tr>

                        @endforeach

                        </tbody>

                    </table>

                    <button class="btn btn-primary btn-sm">
                        Save Changes
                    </button>

                </form>

                <hr>

                {{-- ADD NEW RULE --}}
                <form action="{{ route('payroll.rules.store') }}" method="POST">

                    @csrf

                    <div class="row g-2">

                        <div class="col-md-3">
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   placeholder="Rule Name (e.g Transport)">
                        </div>

                        <div class="col-md-3">
                            <select name="type" class="form-select">
                                <option value="earning">Earning</option>
                                <option value="deduction">Deduction</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select name="formula_type" class="form-select">
                                <option value="fixed">Fixed</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="number"
                                   name="value"
                                   step="0.01"
                                   class="form-control"
                                   placeholder="Value">
                        </div>

                        <div class="col-md-1">
                            <button class="btn btn-success w-100">
                                +
                            </button>
                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>




<!-- Salary Calculator Modal -->
<div class="modal fade"
     id="salaryCalculatorModal"
     tabindex="-1"
     aria-labelledby="salaryCalculatorModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="salaryCalculatorModalLabel">
                    <i class="bi bi-calculator"></i> Salary Calculator
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                </button>
            </div>

            <div class="modal-body">

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

    </div>

</div>