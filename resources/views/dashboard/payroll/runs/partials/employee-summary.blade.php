{{--
    employee-summary.blade.php
    Loaded via AJAX into #employeePayrollSummary when an employee is selected.
    All mutations go through storeAdjustment() / deleteAdjustment() / updateItemField()
    → engine->build() → reload.

    Required variables:
        $payroll          — Payroll model (with ->employee, ->items, ->payroll_run_id)
        $assignableRules  — optional Collection; auto-queried if not passed
--}}

@php
    $assignableRules ??= \App\Models\PayrollRule::where('requires_assignment', true)
        ->where('active', 1)
        ->orderBy('type')
        ->orderBy('name')
        ->get();

    $adjustments = \App\Models\PayrollRunAdjustment::where('payroll_run_id', $payroll->payroll_run_id)
        ->where('employee_id', $payroll->employee_id)
        ->where('active', 1)
        ->get();

    $taxProfileLabels = [
        'taxable'     => 'PAYE + NAPSA',
        'napsa_only'  => 'NAPSA only',
        'non_taxable' => 'Non-taxable',
    ];
    $taxProfileClasses = [
        'taxable'     => 'es-badge es-badge-taxable',
        'napsa_only'  => 'es-badge es-badge-napsa',
        'non_taxable' => 'es-badge es-badge-none',
    ];

    $builtInEarnings   = ['P00', '010', '023', 'P06'];
    $builtInDeductions = ['D00', 'D02', 'D11'];
@endphp

{{-- ════════════════════════════════════════════════════════════════
     STYLES
════════════════════════════════════════════════════════════════ --}}
<style>
.es-wrap {
    --es-border:       #e8e9ec;
    --es-text:         #1a1d23;
    --es-muted:        #75798a;
    --es-bg-subtle:    #f7f8fa;
    --es-accent:       #2f6f4e;
    --es-accent-soft:  #e7f3ec;
    --es-danger:       #c4453a;
    --es-danger-soft:  #fbeae8;
    --es-warn:         #b8842a;
    --es-warn-soft:    #fbf2e3;
    --es-blue:         #2c6ca3;
    --es-blue-soft:    #e3eef8;
    --es-radius:       8px;
    font-size: 0.875rem;
    color: var(--es-text);
}

/* ── Header ─────────────────────────────────────────────────── */
.es-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--es-border);
}
.es-emp-name { font-weight: 600; font-size: 1rem; }
.es-emp-id   { font-size: 0.78rem; color: var(--es-muted); margin-top: 2px; }
.es-net-label {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--es-muted);
    text-align: right;
    margin-bottom: 2px;
}
.es-net-amount {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--es-accent);
    text-align: right;
}

/* ── Payslip columns ─────────────────────────────────────── */
.es-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.es-section-heading {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid var(--es-border);
}
.es-section-heading.earn { color: var(--es-accent); border-color: var(--es-accent-soft); }
.es-section-heading.ded  { color: var(--es-danger);  border-color: var(--es-danger-soft); }

.es-table { width: 100%; border-collapse: collapse; }
.es-table td {
    padding: 0.4rem 0;
    font-size: 0.83rem;
    vertical-align: middle;
    border: none;
    border-bottom: 1px solid #f0f1f3;
}
.es-table td:last-child {
    text-align: right;
    font-variant-numeric: tabular-nums;
    width: 110px;
}
.es-table tbody tr:last-child td { border-bottom: none; }
.es-table tfoot td {
    border-top: 1px solid var(--es-border);
    padding-top: 0.55rem;
    font-weight: 600;
    font-size: 0.84rem;
}
.es-table tfoot td:last-child { color: var(--es-text); }

.es-plain {
    display: inline-block;
    padding: 0.2rem 0.35rem;
    color: var(--es-text);
}
.es-input, .es-input-num {
    border: 1px solid transparent;
    border-radius: 5px;
    background: transparent;
    font: inherit;
    color: inherit;
    padding: 0.2rem 0.35rem;
    outline: none;
    transition: border-color .12s ease, background .12s ease;
}
.es-input     { width: 100%; }
.es-input-num { width: 100%; text-align: right; }
.es-input:hover, .es-input-num:hover {
    border-color: var(--es-border);
    background: #fff;
}
.es-input:focus, .es-input-num:focus {
    border-color: var(--es-accent);
    background: #fff;
    box-shadow: 0 0 0 3px var(--es-accent-soft);
}
.es-noop { color: var(--es-muted); font-size: 0.78rem; }

/* ── Active adjustments ──────────────────────────────────── */
.es-adj-heading {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--es-muted);
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid var(--es-border);
}
.es-adj-table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
.es-adj-table thead th {
    text-align: left;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--es-muted);
    padding: 0 0.75rem 0.45rem;
    border-bottom: 1px solid var(--es-border);
    white-space: nowrap;
}
.es-adj-table thead th:first-child { padding-left: 0; }
.es-adj-table thead th:last-child  { padding-right: 0; text-align: right; }
.es-adj-table tbody td {
    padding: 0.45rem 0.75rem;
    font-size: 0.83rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f1f3;
}
.es-adj-table tbody td:first-child { padding-left: 0; }
.es-adj-table tbody td:last-child  { padding-right: 0; text-align: right; }
.es-adj-table tbody tr:last-child td { border-bottom: none; }

/* ── Badges ──────────────────────────────────────────────── */
.es-badge {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 0.18rem 0.5rem;
    border-radius: 999px;
}
.es-badge-earn    { background: var(--es-accent-soft); color: var(--es-accent); }
.es-badge-ded     { background: var(--es-danger-soft); color: var(--es-danger); }
.es-badge-taxable { background: var(--es-warn-soft);   color: var(--es-warn); }
.es-badge-napsa   { background: var(--es-blue-soft);   color: var(--es-blue); }
.es-badge-none    { background: var(--es-bg-subtle);   color: var(--es-muted); }

/* ── Remove button ───────────────────────────────────────── */
.es-btn-remove {
    border: 1px solid var(--es-border);
    background: transparent;
    color: var(--es-muted);
    padding: 0.25rem 0.65rem;
    border-radius: 5px;
    font-size: 0.72rem;
    font-weight: 500;
    cursor: pointer;
    transition: all .12s ease;
}
.es-btn-remove:hover {
    background: var(--es-danger-soft);
    color: var(--es-danger);
    border-color: var(--es-danger);
}

/* ── Add adjustment panel ────────────────────────────────── */
.es-add-panel {
    background: var(--es-bg-subtle);
    border: 1px solid var(--es-border);
    border-radius: var(--es-radius);
    padding: 1rem 1.1rem;
}
.es-add-heading {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--es-text);
    margin-bottom: 0.85rem;
}

/* ── Mode toggle ─────────────────────────────────────────── */
.es-mode-toggle {
    display: flex;
    width: fit-content;
    border: 1px solid var(--es-border);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 1rem;
}
.es-mode-btn {
    border: none;
    background: transparent;
    padding: 0.38rem 0.9rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--es-muted);
    cursor: pointer;
    transition: background .12s, color .12s;
    line-height: 1;
}
.es-mode-btn.active { background: var(--es-text); color: #fff; }

/* ── Rule picker cards ───────────────────────────────────── */
.es-rule-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 0.5rem;
    margin-bottom: 0.85rem;
}
.es-rule-card {
    background: #fff;
    border: 1px solid var(--es-border);
    border-radius: 6px;
    padding: 0.6rem 0.75rem;
    cursor: pointer;
    transition: border-color .12s, background .12s;
    user-select: none;
}
.es-rule-card:hover    { border-color: var(--es-accent); background: var(--es-accent-soft); }
.es-rule-card.selected { border-color: var(--es-accent); background: var(--es-accent-soft); }
.es-rule-card-name {
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--es-text);
    margin-bottom: 4px;
}
.es-rule-card-meta { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }

/* ── Selected-rule banner ────────────────────────────────── */
.es-rule-banner {
    display: none;
    align-items: center;
    justify-content: space-between;
    background: var(--es-accent-soft);
    border: 1px solid var(--es-accent);
    border-radius: 6px;
    padding: 0.45rem 0.75rem;
    margin-bottom: 0.85rem;
    font-size: 0.82rem;
}
.es-rule-banner-label { color: var(--es-accent); font-weight: 500; }
.es-rule-banner-clear {
    border: none;
    background: transparent;
    color: var(--es-muted);
    cursor: pointer;
    font-size: 0.76rem;
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    transition: background .1s, color .1s;
}
.es-rule-banner-clear:hover { background: #fff; color: var(--es-danger); }

.es-no-rules {
    font-size: 0.8rem;
    color: var(--es-muted);
    padding: 0.5rem 0;
    margin-bottom: 0.75rem;
}

/* ── Form grid ───────────────────────────────────────────── */
.es-add-grid {
    display: grid;
    grid-template-columns: 1.8fr 1fr 1.6fr 1fr auto;
    gap: 0.5rem;
    align-items: end;
    margin-bottom: 0.5rem;
}
.es-field label {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--es-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.3rem;
}
.es-field input, .es-field select {
    width: 100%;
    border: 1px solid var(--es-border);
    border-radius: 6px;
    padding: 0.42rem 0.6rem;
    font-size: 0.82rem;
    background: #fff;
    color: var(--es-text);
    outline: none;
    font-family: inherit;
    transition: border-color .12s;
}
.es-field input:focus, .es-field select:focus {
    border-color: var(--es-accent);
    box-shadow: 0 0 0 3px var(--es-accent-soft);
}
.es-field input[readonly] {
    background: var(--es-bg-subtle);
    color: var(--es-muted);
    cursor: default;
}
.es-field input[readonly]:focus {
    border-color: var(--es-border);
    box-shadow: none;
}

/* ── Submit button ───────────────────────────────────────── */
.es-btn-submit {
    height: 36px;
    border: none;
    background: var(--es-accent);
    color: #fff;
    padding: 0 1rem;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    transition: opacity .12s ease;
}
.es-btn-submit:hover    { opacity: 0.88; }
.es-btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }

.es-error {
    font-size: 0.76rem;
    color: var(--es-danger);
    margin-top: 0.4rem;
    min-height: 1rem;
}
</style>

{{-- ════════════════════════════════════════════════════════════════
     MARKUP
════════════════════════════════════════════════════════════════ --}}
<div class="es-wrap"
     data-payroll-id="{{ $payroll->id }}"
     data-employee-id="{{ $payroll->employee_id }}"
     data-run-id="{{ $payroll->payroll_run_id }}">

    {{-- ── Employee header ──────────────────────────────────── --}}
    <div class="es-header">
        <div>
            <div class="es-emp-name">
                {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}
            </div>
            <div class="es-emp-id">{{ $payroll->employee->employee_id }}</div>
        </div>
        <div>
            <div class="es-net-label">Net Pay</div>
            <div class="es-net-amount">K {{ number_format($payroll->net_pay, 2) }}</div>
        </div>
    </div>

    {{-- ── Payslip items ────────────────────────────────────── --}}
    <div class="es-cols" id="payslipItems">

        {{-- EARNINGS --}}
        <div>
            <div class="es-section-heading earn">Earnings</div>
            <table class="es-table" id="earningsTable">
                <tbody>
                    @foreach($payroll->items->where('type', 'earning') as $item)
                    <tr data-item-id="{{ $item->id }}">

                        <td>
                            @if(in_array($item->code, $builtInEarnings))
                                <span class="es-plain">{{ $item->description }}</span>
                            @else
                                <input type="text"
                                       value="{{ $item->description }}"
                                       class="es-input"
                                       onblur="updateItemField(this, {{ $item->id }}, 'description')">
                            @endif
                        </td>

                        <td>
                            @if(in_array($item->code, $builtInEarnings))
                                <span class="es-plain">{{ number_format($item->amount, 2) }}</span>
                            @else
                                <input type="number"
                                       value="{{ $item->amount }}"
                                       class="es-input-num"
                                       onblur="updateItemField(this, {{ $item->id }}, 'amount')">
                            @endif
                        </td>

                        <td style="width:32px">
                            @if(!in_array($item->code, $builtInEarnings))
                                <span class="es-noop" title="Remove via adjustment">—</span>
                            @endif
                        </td>

                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total Earnings</td>
                        <td>K {{ number_format($payroll->total_income, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- DEDUCTIONS --}}
        <div>
            <div class="es-section-heading ded">Deductions</div>
            <table class="es-table" id="deductionsTable">
                <tbody>
                    @foreach($payroll->items->where('type', 'deduction') as $item)
                    <tr data-item-id="{{ $item->id }}">

                        <td>
                            @if(in_array($item->code, $builtInDeductions))
                                <span class="es-plain">{{ $item->description }}</span>
                            @else
                                <input type="text"
                                       value="{{ $item->description }}"
                                       class="es-input"
                                       onblur="updateItemField(this, {{ $item->id }}, 'description')">
                            @endif
                        </td>

                        <td>
                            @if(in_array($item->code, $builtInDeductions))
                                <span class="es-plain">{{ number_format($item->amount, 2) }}</span>
                            @else
                                <input type="number"
                                       value="{{ $item->amount }}"
                                       class="es-input-num"
                                       onblur="updateItemField(this, {{ $item->id }}, 'amount')">
                            @endif
                        </td>

                        <td style="width:32px">
                            @if(!in_array($item->code, $builtInDeductions))
                                <span class="es-noop" title="Remove via adjustment">—</span>
                            @endif
                        </td>

                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total Deductions</td>
                        <td>K {{ number_format($payroll->total_deductions, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>

    {{-- ── Active adjustments ───────────────────────────────── --}}
    @if($adjustments->isNotEmpty())
    <div class="mb-4">
        <div class="es-adj-heading">Active Adjustments</div>
        <table class="es-adj-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Tax profile</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustments as $adj)
                <tr data-adj-id="{{ $adj->id }}">

                    <td>{{ $adj->name }}</td>

                    <td>
                        @if($adj->type === 'earning')
                            <span class="es-badge es-badge-earn">Earning</span>
                        @else
                            <span class="es-badge es-badge-ded">Deduction</span>
                        @endif
                    </td>

                    <td>
                        @if($adj->type === 'earning')
                            @php $p = $taxProfileClasses[$adj->tax_profile] ?? 'es-badge es-badge-none'; @endphp
                            <span class="{{ $p }}">
                                {{ $taxProfileLabels[$adj->tax_profile] ?? '—' }}
                            </span>
                        @else
                            <span style="color:var(--es-muted)">—</span>
                        @endif
                    </td>

                    <td>K {{ number_format($adj->value, 2) }}</td>

                    <td>
                        <button type="button"
                                class="es-btn-remove"
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

    {{-- ── Add adjustment ───────────────────────────────────── --}}
    <div class="es-add-panel">

        <div class="es-add-heading">Add adjustment</div>

        {{-- Mode switcher --}}
        <div class="es-mode-toggle" role="group" aria-label="Adjustment mode">
            <button type="button" class="es-mode-btn active" id="esModeRule"   onclick="esSetMode('rule')">Use a rule</button>
            <button type="button" class="es-mode-btn"        id="esModeCustom" onclick="esSetMode('custom')">Custom</button>
        </div>

        {{-- Rule picker --}}
        <div id="esRuleSection">

            @if($assignableRules->isEmpty())
                <p class="es-no-rules">
                    No assignable rules configured. Switch to <strong>Custom</strong> to add a free-form adjustment,
                    or mark rules as <em>requires_assignment</em> in Payroll rules.
                </p>
            @else
                <div class="es-rule-grid" id="esRuleGrid">
                    @foreach($assignableRules as $rule)
                    <div class="es-rule-card"
                         data-rule-id="{{ $rule->id }}"
                         data-name="{{ $rule->name }}"
                         data-type="{{ $rule->type }}"
                         data-tax="{{ $rule->tax_profile }}"
                         data-value="{{ $rule->formula_type === 'fixed' && $rule->value > 0 ? $rule->value : '' }}"
                         onclick="esSelectRule(this)">

                        <div class="es-rule-card-name">{{ $rule->name }}</div>
                        <div class="es-rule-card-meta">

                            @if($rule->type === 'earning')
                                <span class="es-badge es-badge-earn">Earning</span>
                                <span class="{{ $taxProfileClasses[$rule->tax_profile] ?? 'es-badge es-badge-none' }}">
                                    {{ $taxProfileLabels[$rule->tax_profile] ?? '—' }}
                                </span>
                            @else
                                <span class="es-badge es-badge-ded">Deduction</span>
                            @endif

                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            <div class="es-rule-banner" id="esRuleBanner">
                <span class="es-rule-banner-label" id="esRuleBannerText"></span>
                <button type="button" class="es-rule-banner-clear" onclick="esClearRule()">Clear</button>
            </div>

        </div>

        {{-- Form fields --}}
        <div class="es-add-grid">

        <div class="es-field" style="display:none;" aria-hidden="true">
            <label for="adjRuleId">Rule ID</label>
            <input type="text"
                   id="adjRuleId"
                   placeholder="Rule ID">
            </div>
            
            <div class="es-field">
                <label for="adjName">Description</label>
                <input type="text"
                       id="adjName"
                       placeholder="e.g. Overtime, Gratuity">
            </div>

            <div class="es-field">
                <label for="adjType">Type</label>
                <select id="adjType" onchange="toggleTaxProfile()">
                    <option value="earning">Earning</option>
                    <option value="deduction">Deduction</option>
                </select>
            </div>

            <div class="es-field" id="taxProfileGroup">
                <label for="adjTaxProfile">Tax treatment</label>
                <select id="adjTaxProfile">
                    <option value="taxable">PAYE + NAPSA — Overtime, Bonus</option>
                    <option value="napsa_only">NAPSA only — Gratuity, PILON</option>
                    <option value="non_taxable">Non-taxable — Reimbursements</option>
                </select>
            </div>

            <div class="es-field">
                <label for="adjAmount">Amount (K)</label>
                <input type="number"
                       id="adjAmount"
                       placeholder="0.00"
                       min="0"
                       step="0.01"
                       style="text-align:right">
            </div>

            <button type="button"
                    class="es-btn-submit"
                    onclick="submitAdjustment()"
                    id="adjSubmitBtn">
                <span id="adjBtnText">Add &amp; recalculate</span>
                <span id="adjBtnSpinner"
                      class="spinner-border spinner-border-sm d-none"
                      role="status"
                      aria-hidden="true"></span>
            </button>

        </div>

        <div id="adjError" class="es-error"></div>

    </div>

</div>
