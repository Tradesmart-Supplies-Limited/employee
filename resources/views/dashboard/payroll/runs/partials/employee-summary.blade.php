<!-- <div class="row" data-payroll-id="{{ $payroll->id }}">

<div class="card p-3 mb-3">

    <div class="row g-2">

        <div class="col-md-4">
            <input type="text" id="newDesc" class="form-control" placeholder="Description">
        </div>

        <div class="col-md-3">
            <select id="newType" class="form-select">
                <option value="earning">Earning</option>
                <option value="deduction">Deduction</option>
            </select>
        </div>

        <div class="col-md-3">
            <input type="number" id="newAmount" class="form-control" placeholder="Amount">
        </div>

        <div class="col-md-2">
            <button class="btn btn-success w-100" onclick="addItem()">
                Add
            </button>
        </div>

    </div>

</div>

    <div class="col-md-6">

        <h6>Earnings</h6>

        <table class="table table-sm" id="earningsTable">

            @foreach($payroll->items->where('type','earning') as $item)

            <tr data-id="{{ $item->id }}">

                <td>
                    <input type="text" value="{{ $item->description }}" class="form-control form-control-sm"
                        onblur="updateItem(this, {{ $item->id }}, 'description')">
                </td>

                <td>
                    <input type="number" value="{{ $item->amount }}" class="form-control form-control-sm text-end"
                        onblur="updateItem(this, {{ $item->id }}, 'amount')">
                </td>

                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem({{ $item->id }})">
                        Delete
                    </button>
                </td>

            </tr>

            @endforeach

        </table>

    </div>

    <div class="col-md-6">

        <h6>Deductions</h6>

            <table class="table table-sm" id="deductionsTable">

            @foreach($payroll->items->where('type','deduction') as $item)

            <tr data-id="{{ $item->id }}">

                <td>
                    <input type="text" value="{{ $item->description }}" class="form-control form-control-sm"
                        onblur="updateItem(this, {{ $item->id }}, 'description')">
                </td>

                <td>
                    <input type="number" value="{{ $item->amount }}" class="form-control form-control-sm text-end"
                        onblur="updateItem(this, {{ $item->id }}, 'amount')">
                </td>

                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem({{ $item->id }})">
                        Delete
                    </button>
                </td>

            </tr>

            @endforeach

        </table>

    </div>

</div> -->

{{--
    employee-summary.blade.php
    Loaded via AJAX into #employeePayrollSummary when an employee is selected.
    All mutations go through storeAdjustment() → engine->build() → reload.
--}}

<div data-payroll-id="{{ $payroll->id }}"
     data-employee-id="{{ $payroll->employee_id }}"
     data-run-id="{{ $payroll->payroll_run_id }}">

    {{-- ═══════════════════════════════════════════════════════════
         EMPLOYEE HEADER
    ═══════════════════════════════════════════════════════════ --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <strong>{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</strong>
            <span class="text-muted ms-2">{{ $payroll->employee->employee_id }}</span>
        </div>

        <div class="text-end">
            <div class="small text-muted">Net Pay</div>
            <div class="fs-5 fw-bold text-success">
                K {{ number_format($payroll->net_pay, 2) }}
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         PAYSLIP ITEMS — earnings + deductions side by side
    ═══════════════════════════════════════════════════════════ --}}
    <div class="row mb-4" id="payslipItems">

        {{-- EARNINGS --}}
        <div class="col-md-6">

            <h6 class="text-success border-bottom pb-1">Earnings</h6>

            <table class="table table-sm table-borderless" id="earningsTable">
                <tbody>
                    @foreach($payroll->items->where('type', 'earning') as $item)
                    <tr data-item-id="{{ $item->id }}">

                        <td class="ps-0">
                            {{-- Built-in items (BASIC, HSNG, 010, 023) are read-only --}}
                            @if(in_array($item->code, ['P00', '010', '023', 'P06']))
                                <span class="form-control-plaintext form-control-sm py-0">
                                    {{ $item->description }}
                                </span>
                            @else
                                <input type="text"
                                       value="{{ $item->description }}"
                                       class="form-control form-control-sm"
                                       onblur="updateItemField(this, {{ $item->id }}, 'description')">
                            @endif
                        </td>

                        <td class="text-end" style="width:110px">
                            @if(in_array($item->code, ['P00', '010', '023', 'P06']))
                                <span class="form-control-plaintext form-control-sm py-0 text-end">
                                    {{ number_format($item->amount, 2) }}
                                </span>
                            @else
                                <input type="number"
                                       value="{{ $item->amount }}"
                                       class="form-control form-control-sm text-end"
                                       onblur="updateItemField(this, {{ $item->id }}, 'amount')">
                            @endif
                        </td>

                        <td class="text-end pe-0" style="width:40px">
                            @if(!in_array($item->code, ['P00', '010', '023', 'P06']))
                                {{-- Non built-in items cannot be deleted directly.
                                     Delete the adjustment instead. --}}
                                <span class="text-muted small" title="Remove via adjustment">—</span>
                            @endif
                        </td>

                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-top">
                    <tr>
                        <td class="fw-semibold ps-0">Total Earnings</td>
                        <td class="text-end fw-semibold">
                            K {{ number_format($payroll->total_income, 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

        </div>

        {{-- DEDUCTIONS --}}
        <div class="col-md-6">

            <h6 class="text-danger border-bottom pb-1">Deductions</h6>

            <table class="table table-sm table-borderless" id="deductionsTable">
                <tbody>
                    @foreach($payroll->items->where('type', 'deduction') as $item)
                    <tr data-item-id="{{ $item->id }}">

                        <td class="ps-0">
                            @if(in_array($item->code, ['D00', 'D02', 'D11']))
                                <span class="form-control-plaintext form-control-sm py-0">
                                    {{ $item->description }}
                                </span>
                            @else
                                <input type="text"
                                       value="{{ $item->description }}"
                                       class="form-control form-control-sm"
                                       onblur="updateItemField(this, {{ $item->id }}, 'description')">
                            @endif
                        </td>

                        <td class="text-end" style="width:110px">
                            @if(in_array($item->code, ['D00', 'D02', 'D11']))
                                <span class="form-control-plaintext form-control-sm py-0 text-end">
                                    {{ number_format($item->amount, 2) }}
                                </span>
                            @else
                                <input type="number"
                                       value="{{ $item->amount }}"
                                       class="form-control form-control-sm text-end"
                                       onblur="updateItemField(this, {{ $item->id }}, 'amount')">
                            @endif
                        </td>

                        <td class="text-end pe-0" style="width:40px">
                            @if(!in_array($item->code, ['D00', 'D02', 'D11']))
                                <span class="text-muted small" title="Remove via adjustment">—</span>
                            @endif
                        </td>

                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-top">
                    <tr>
                        <td class="fw-semibold ps-0">Total Deductions</td>
                        <td class="text-end fw-semibold">
                            K {{ number_format($payroll->total_deductions, 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ACTIVE ADJUSTMENTS — shows what's been added so far
         with a delete button that triggers a rebuild
    ═══════════════════════════════════════════════════════════ --}}
    @php
        $adjustments = \App\Models\PayrollRunAdjustment::where('payroll_run_id', $payroll->payroll_run_id)
            ->where('employee_id', $payroll->employee_id)
            ->where('active', 1)
            ->get();
    @endphp

    @if($adjustments->isNotEmpty())
    <div class="mb-4">

        <h6 class="border-bottom pb-1">Active Adjustments</h6>

        <table class="table table-sm">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Tax Profile</th>
                    <th class="text-end">Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustments as $adj)
                <tr data-adj-id="{{ $adj->id }}">
                    <td>{{ $adj->name }}</td>
                    <td>
                        <span class="badge {{ $adj->type === 'earning' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($adj->type) }}
                        </span>
                    </td>
                    <td>
                        @if($adj->type === 'earning')
                            @php
                                $profiles = [
                                    'taxable'     => ['label' => 'PAYE + NAPSA',  'class' => 'bg-warning text-dark'],
                                    'napsa_only'  => ['label' => 'NAPSA Only',    'class' => 'bg-info text-dark'],
                                    'non_taxable' => ['label' => 'Non-Taxable',   'class' => 'bg-secondary'],
                                ];
                                $p = $profiles[$adj->tax_profile] ?? ['label' => '—', 'class' => 'bg-light text-dark'];
                            @endphp
                            <span class="badge {{ $p['class'] }}">{{ $p['label'] }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">K {{ number_format($adj->value, 2) }}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteAdjustment({{ $adj->id }})">
                            Remove
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         ADD ADJUSTMENT FORM
    ═══════════════════════════════════════════════════════════ --}}
    <div class="card border-0 bg-light p-3">

        <h6 class="mb-3">Add Adjustment</h6>

        <div class="row g-2 align-items-end">

            {{-- Description --}}
            <div class="col-md-3">
                <label class="form-label small mb-1">Description</label>
                <input type="text"
                       id="adjName"
                       class="form-control form-control-sm"
                       placeholder="e.g. Overtime, Gratuity">
            </div>

            {{-- Type --}}
            <div class="col-md-2">
                <label class="form-label small mb-1">Type</label>
                <select id="adjType"
                        class="form-select form-select-sm"
                        onchange="toggleTaxProfile()">
                    <option value="earning">Earning</option>
                    <option value="deduction">Deduction</option>
                </select>
            </div>

            {{-- Tax Profile — only visible for earnings --}}
            <div class="col-md-3" id="taxProfileGroup">
                <label class="form-label small mb-1">
                    Tax Treatment
                    <span class="text-muted">(earnings only)</span>
                </label>
                <select id="adjTaxProfile" class="form-select form-select-sm">
                    <option value="taxable">
                        PAYE + NAPSA — Overtime, Bonus, Commission
                    </option>
                    <option value="napsa_only">
                        NAPSA Only — Gratuity, PILON, Ex gratia
                    </option>
                    <option value="non_taxable">
                        Non-Taxable — Reimbursements
                    </option>
                </select>
            </div>

            {{-- Amount --}}
            <div class="col-md-2">
                <label class="form-label small mb-1">Amount (K)</label>
                <input type="number"
                       id="adjAmount"
                       class="form-control form-control-sm text-end"
                       placeholder="0.00"
                       min="0"
                       step="0.01">
            </div>

            {{-- Submit --}}
            <div class="col-md-2">
                <button class="btn btn-success btn-sm w-100"
                        onclick="submitAdjustment()"
                        id="adjSubmitBtn">
                    <span id="adjBtnText">Add &amp; Recalculate</span>
                    <span id="adjBtnSpinner"
                          class="spinner-border spinner-border-sm d-none"
                          role="status"></span>
                </button>
            </div>

        </div>

        {{-- Inline validation message --}}
        <div id="adjError" class="text-danger small mt-2 d-none"></div>

    </div>

</div>
