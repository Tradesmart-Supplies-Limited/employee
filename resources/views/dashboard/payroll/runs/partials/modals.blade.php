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