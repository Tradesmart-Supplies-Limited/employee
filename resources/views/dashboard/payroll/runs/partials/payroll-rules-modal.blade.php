{{--
    payroll-rules-modal.blade.php

    Fully AJAX-driven. No page reloads. Every edit, add, and delete
    talks to /payroll/rules/* and patches the DOM directly.

    Required routes:
        GET    /payroll/rules            → index   (list/search, JSON)
        POST   /payroll/rules            → store   (create, JSON)
        PATCH  /payroll/rules/{rule}     → update  (single field or full row, JSON)
        DELETE /payroll/rules/{rule}     → destroy (JSON)
        POST   /payroll/rules/seed       → seedDefaults (redirect)
--}}

<div class="modal fade" id="payrollRulesModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content rules-modal">

            {{-- ════════════════════════════════════════════════════════
                 HEADER
            ════════════════════════════════════════════════════════ --}}
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Payroll rules</h5>
                    <p class="text-muted small mb-0">Earnings, deductions, and how each one is taxed</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">

                {{-- ════════════════════════════════════════════════════
                     TOOLBAR — search, filter, add button
                ════════════════════════════════════════════════════ --}}
                <div class="rules-toolbar">

                    <div class="rules-search">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input type="text"
                               id="ruleSearch"
                               placeholder="Search rules by name, code, or category"
                               autocomplete="off">
                    </div>

                    <div class="rules-filter-group">
                        <button class="rules-filter-chip active" data-filter="all">All</button>
                        <button class="rules-filter-chip" data-filter="earning">Earnings</button>
                        <button class="rules-filter-chip" data-filter="deduction">Deductions</button>
                    </div>

                    <button class="btn-add-rule" id="toggleAddRule">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Add rule
                    </button>

                </div>

                {{-- ════════════════════════════════════════════════════
                     ADD RULE — collapsible, sits above the table
                ════════════════════════════════════════════════════ --}}
                <div id="addRulePanel" class="add-rule-panel d-none">

                    <div class="add-rule-grid">

                        <input type="text" id="newName" class="ar-input ar-name" placeholder="Rule name">
                        <input type="text" id="newCode" class="ar-input ar-code" placeholder="Code">

                        <select id="newType" class="ar-select">
                            <option value="earning">Earning</option>
                            <option value="deduction">Deduction</option>
                        </select>

                        <input type="text" id="newCategory" class="ar-input" placeholder="Category">

                        <select id="newFormulaType" class="ar-select">
                            <option value="fixed">Fixed K</option>
                            <option value="percentage">Percent %</option>
                        </select>

                        <input type="number" id="newValue" class="ar-input text-end" placeholder="0.00" step="0.01">

                        <select id="newAppliesTo" class="ar-select">
                            <option value="BASICPAY">On basic pay</option>
                            <option value="GROSSPAY">On gross pay</option>
                        </select>

                        <select id="newTaxProfile" class="ar-select ar-tax">
                            <option value="taxable">PAYE + NAPSA</option>
                            <option value="napsa_only">NAPSA only</option>
                            <option value="non_taxable">Not taxed</option>
                        </select>

                    </div>

                    <div class="add-rule-flags">
                        <label><input type="checkbox" id="newRecurring" checked> Recurring</label>
                        <label><input type="checkbox" id="newPayslip" checked> Show on payslip</label>
                        <label><input type="checkbox" id="newStatutory"> Statutory</label>
                        <label><input type="checkbox" id="newPensionable"> Pensionable</label>
                        <label><input type="checkbox" id="newRequiresAssignment"> Needs assignment</label>
                    </div>

                    <div class="add-rule-actions">
                        <span class="ar-error text-danger small" id="addRuleError"></span>
                        <button class="btn-cancel" id="cancelAddRule">Cancel</button>
                        <button class="btn-save" id="saveNewRule">Create rule</button>
                    </div>

                </div>

                {{-- ════════════════════════════════════════════════════
                     TABLE
                ════════════════════════════════════════════════════ --}}
                <div class="rules-table-wrap">

                    <table class="rules-table">
                        <thead>
                            <tr>
                                <th class="col-name">Name</th>
                                <th class="col-code">Code</th>
                                <th class="col-amount">Amount</th>
                                <th class="col-on">On</th>
                                <th class="col-tax">
                                    Tax
                                    <span class="info-dot" title="Controls whether PAYE and NAPSA are calculated on this earning">?</span>
                                </th>
                                <th class="col-flags">Flags</th>
                                <th class="col-active">Active</th>
                                <th class="col-actions"></th>
                            </tr>
                        </thead>
                        <tbody id="rulesTableBody">
                            <tr class="rules-loading-row">
                                <td colspan="8">Loading rules…</td>
                            </tr>
                        </tbody>
                    </table>

                    <div id="rulesEmptyState" class="rules-empty d-none">
                        <p class="mb-2"><strong>No rules yet</strong></p>
                        <p class="text-muted small mb-3">Load the standard Zambian rule set to get started, or add your own above.</p>
                        <form method="POST" action="{{ route('payroll.rules.seedDefaults') }}">
                            @csrf
                            <button class="btn-seed">Load standard rules</button>
                        </form>
                    </div>

                    <div id="rulesNoMatch" class="rules-empty d-none">
                        <p class="text-muted small">No rules match your search.</p>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     STYLES
════════════════════════════════════════════════════════════════ --}}
<style>
.rules-modal {
    --rm-border: #e8e9ec;
    --rm-text: #1a1d23;
    --rm-muted: #75798a;
    --rm-bg-subtle: #f7f8fa;
    --rm-accent: #2f6f4e;
    --rm-accent-soft: #e7f3ec;
    --rm-danger: #c4453a;
    --rm-danger-soft: #fbeae8;
    --rm-warn: #b8842a;
    --rm-warn-soft: #fbf2e3;
    --rm-radius: 8px;
    font-size: 0.875rem;
    color: var(--rm-text);
}

.rules-modal .modal-header { border-bottom: 1px solid var(--rm-border); padding: 1.1rem 1.4rem; }
.rules-modal .modal-title { font-weight: 600; font-size: 1.02rem; letter-spacing: -0.01em; }

/* ── Toolbar ─────────────────────────────────────────────────────── */
.rules-toolbar {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.9rem 1.4rem;
    border-bottom: 1px solid var(--rm-border);
    background: #fff;
}

.rules-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    max-width: 320px;
    padding: 0.45rem 0.7rem;
    border: 1px solid var(--rm-border);
    border-radius: var(--rm-radius);
    background: var(--rm-bg-subtle);
    color: var(--rm-muted);
}
.rules-search input {
    border: none;
    background: transparent;
    outline: none;
    width: 100%;
    font-size: 0.85rem;
    color: var(--rm-text);
}
.rules-search input::placeholder { color: #9a9ea8; }

.rules-filter-group { display: flex; gap: 0.35rem; }
.rules-filter-chip {
    border: 1px solid var(--rm-border);
    background: #fff;
    color: var(--rm-muted);
    padding: 0.4rem 0.75rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 500;
    cursor: pointer;
    transition: all .12s ease;
}
.rules-filter-chip:hover { background: var(--rm-bg-subtle); }
.rules-filter-chip.active {
    background: var(--rm-text);
    border-color: var(--rm-text);
    color: #fff;
}

.btn-add-rule {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    border: none;
    background: var(--rm-text);
    color: #fff;
    padding: 0.5rem 0.95rem;
    border-radius: var(--rm-radius);
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
    transition: opacity .12s ease;
}
.btn-add-rule:hover { opacity: 0.85; }

/* ── Add rule panel ──────────────────────────────────────────────── */
.add-rule-panel {
    padding: 1.1rem 1.4rem;
    background: var(--rm-bg-subtle);
    border-bottom: 1px solid var(--rm-border);
    animation: slideDown .15s ease;
}
@keyframes slideDown { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

.add-rule-grid {
    display: grid;
    grid-template-columns: 1.6fr 0.9fr 1fr 1fr 1fr 0.9fr 1fr 1.3fr;
    gap: 0.5rem;
    margin-bottom: 0.7rem;
}

.ar-input, .ar-select {
    border: 1px solid var(--rm-border);
    border-radius: 6px;
    padding: 0.45rem 0.6rem;
    font-size: 0.82rem;
    background: #fff;
    color: var(--rm-text);
    width: 100%;
}
.ar-input:focus, .ar-select:focus { outline: none; border-color: var(--rm-accent); box-shadow: 0 0 0 3px var(--rm-accent-soft); }
.ar-code { font-family: 'SF Mono', Consolas, monospace; }

.add-rule-flags {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 0.85rem;
}
.add-rule-flags label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: var(--rm-muted);
    cursor: pointer;
}
.add-rule-flags input[type="checkbox"] { cursor: pointer; }

.add-rule-actions { display: flex; align-items: center; justify-content: flex-end; gap: 0.6rem; }
.ar-error { margin-right: auto; }

.btn-cancel {
    border: 1px solid var(--rm-border);
    background: #fff;
    color: var(--rm-muted);
    padding: 0.45rem 0.9rem;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
}
.btn-save {
    border: none;
    background: var(--rm-accent);
    color: #fff;
    padding: 0.45rem 1rem;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
}
.btn-save:hover { opacity: 0.9; }
.btn-save:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Table ───────────────────────────────────────────────────────── */
.rules-table-wrap { overflow-x: auto; }
.rules-table { width: 100%; border-collapse: collapse; }

.rules-table thead th {
    text-align: left;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--rm-muted);
    padding: 0.7rem 1rem;
    border-bottom: 1px solid var(--rm-border);
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 1;
    white-space: nowrap;
}

.info-dot {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 13px; height: 13px;
    border-radius: 50%;
    background: #d8dae0;
    color: #fff;
    font-size: 0.62rem;
    font-weight: 700;
    cursor: help;
    margin-left: 2px;
}

.rules-table tbody tr {
    border-bottom: 1px solid #f0f1f3;
    transition: background .1s ease;
}
.rules-table tbody tr:hover { background: var(--rm-bg-subtle); }
.rules-table tbody tr.rule-inactive { opacity: 0.5; }
.rules-table tbody tr.rule-saving { background: #fffbe9; }
.rules-table tbody tr.rule-deleting { opacity: 0.3; pointer-events: none; }

.rules-table td { padding: 0.55rem 1rem; vertical-align: middle; }

.rule-name-cell { font-weight: 500; }
.rule-category { color: var(--rm-muted); font-size: 0.74rem; }

/* Click-to-edit cells */
.editable {
    cursor: text;
    border-radius: 5px;
    padding: 0.2rem 0.4rem;
    margin: -0.2rem -0.4rem;
    display: inline-block;
    min-width: 24px;
}
.editable:hover { background: #fff; box-shadow: inset 0 0 0 1px var(--rm-border); }
.editable.editing {
    background: #fff;
    box-shadow: inset 0 0 0 1.5px var(--rm-accent);
}
.editable input {
    border: none;
    outline: none;
    background: transparent;
    font: inherit;
    color: inherit;
    width: 100%;
    padding: 0;
}
.editable-code { font-family: 'SF Mono', Consolas, monospace; font-size: 0.78rem; }
.editable-amount { font-variant-numeric: tabular-nums; }

.cell-select {
    border: none;
    background: transparent;
    font: inherit;
    color: inherit;
    cursor: pointer;
    border-radius: 5px;
    padding: 0.2rem 0.3rem;
    margin: -0.2rem -0.3rem;
}
.cell-select:hover { background: #fff; box-shadow: inset 0 0 0 1px var(--rm-border); }
.cell-select:focus { outline: none; box-shadow: inset 0 0 0 1.5px var(--rm-accent); }

.tax-pill {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.18rem 0.5rem;
    border-radius: 999px;
    border: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
}
.tax-pill[data-val="taxable"] { background: var(--rm-warn-soft); color: var(--rm-warn); }
.tax-pill[data-val="napsa_only"] { background: #e3eef8; color: #2c6ca3; }
.tax-pill[data-val="non_taxable"] { background: #eceef0; color: var(--rm-muted); }

.flags-row { display: flex; gap: 0.55rem; flex-wrap: wrap; max-width: 170px; }
.flag-toggle {
    position: relative;
    display: inline-flex;
    cursor: pointer;
    width: 16px;
    height: 16px;
}
.flag-toggle input {
    position: absolute;
    opacity: 0;
    width: 16px;
    height: 16px;
    margin: 0;
    cursor: pointer;
    z-index: 1;
}
.flag-dot {
    position: absolute;
    top: 4px; left: 4px;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #d8dae0;
    transition: background .12s ease, transform .12s ease;
    pointer-events: none;
}
.flag-toggle input:checked ~ .flag-dot { background: var(--rm-accent); }
.flag-toggle:hover .flag-dot { transform: scale(1.35); }
.flag-toggle input:focus-visible ~ .flag-dot { box-shadow: 0 0 0 3px var(--rm-accent-soft); }

.toggle-switch {
    position: relative;
    width: 32px; height: 18px;
    cursor: pointer;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
.toggle-track {
    position: absolute; inset: 0;
    background: #d8dae0;
    border-radius: 999px;
    transition: background .15s ease;
}
.toggle-track::after {
    content: '';
    position: absolute;
    width: 14px; height: 14px;
    top: 2px; left: 2px;
    background: #fff;
    border-radius: 50%;
    transition: transform .15s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,.15);
}
.toggle-switch input:checked + .toggle-track { background: var(--rm-accent); }
.toggle-switch input:checked + .toggle-track::after { transform: translateX(14px); }

.btn-row-delete {
    border: none;
    background: transparent;
    color: #b0b3bd;
    cursor: pointer;
    padding: 0.3rem;
    border-radius: 5px;
    display: inline-flex;
}
.btn-row-delete:hover { background: var(--rm-danger-soft); color: var(--rm-danger); }

.rules-loading-row td { text-align: center; color: var(--rm-muted); padding: 2.5rem 0; }

.rules-empty { text-align: center; padding: 3rem 1rem; }
.btn-seed {
    border: none;
    background: var(--rm-text);
    color: #fff;
    padding: 0.5rem 1.1rem;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
}

.save-flash {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    font-size: 0.7rem;
    color: var(--rm-accent);
    opacity: 0;
    transition: opacity .2s ease;
}
.save-flash.show { opacity: 1; }
</style>

{{-- ════════════════════════════════════════════════════════════════
     SCRIPT
════════════════════════════════════════════════════════════════ --}}
<script>
(function () {

    const ENDPOINTS = {
        list:    "{{ route('payroll.rules.index') }}",
        store:   "{{ route('payroll.rules.store') }}",
        update:  (id) => "{{ url('payroll/rules') }}/" + id,
        destroy: (id) => "{{ url('payroll/rules') }}/" + id,
    };
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
              ?? "{{ csrf_token() }}";

    let allRules     = [];
    let activeFilter = 'all';
    let searchTerm   = '';

    const tbody       = document.getElementById('rulesTableBody');
    const emptyState  = document.getElementById('rulesEmptyState');
    const noMatch     = document.getElementById('rulesNoMatch');
    const searchInput = document.getElementById('ruleSearch');

    /* ──────────────────────────────────────────────────────────────
       LOAD RULES
    ────────────────────────────────────────────────────────────── */
    function loadRules() {
        tbody.innerHTML = '<tr class="rules-loading-row"><td colspan="8">Loading rules…</td></tr>';

        fetch(ENDPOINTS.list)
            .then(r => r.json())
            .then(data => {
                allRules = data.rules || [];
                render();
            })
            .catch(() => {
                tbody.innerHTML = '<tr class="rules-loading-row"><td colspan="8">Could not load rules.</td></tr>';
            });
    }

    /* ──────────────────────────────────────────────────────────────
       RENDER TABLE
    ────────────────────────────────────────────────────────────── */
    function render() {
        let rows = allRules;

        if (activeFilter !== 'all') {
            rows = rows.filter(r => r.type === activeFilter);
        }
        if (searchTerm.trim()) {
            const q = searchTerm.toLowerCase();
            rows = rows.filter(r =>
                (r.name || '').toLowerCase().includes(q) ||
                (r.code || '').toLowerCase().includes(q) ||
                (r.category || '').toLowerCase().includes(q)
            );
        }

        emptyState.classList.toggle('d-none', allRules.length > 0);
        noMatch.classList.toggle('d-none', !(allRules.length > 0 && rows.length === 0));
        tbody.innerHTML = '';

        if (allRules.length === 0 || rows.length === 0) return;

        rows.forEach(rule => tbody.appendChild(buildRow(rule)));
    }

    /* ──────────────────────────────────────────────────────────────
       BUILD A SINGLE ROW
    ────────────────────────────────────────────────────────────── */
    function buildRow(rule) {
        const tr = document.createElement('tr');
        tr.dataset.id = rule.id;
        if (!rule.active) tr.classList.add('rule-inactive');

        tr.innerHTML = `
            <td>
                <div class="rule-name-cell editable" data-field="name" data-id="${rule.id}">${esc(rule.name)}</div>
                <div class="rule-category editable" data-field="category" data-id="${rule.id}">${esc(rule.category || 'No category')}</div>
            </td>
            <td><span class="editable editable-code" data-field="code" data-id="${rule.id}">${esc(rule.code || '—')}</span></td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <select class="cell-select" data-field="formula_type" data-id="${rule.id}" style="font-size:.72rem">
                        <option value="fixed" ${rule.formula_type === 'fixed' ? 'selected' : ''}>K</option>
                        <option value="percentage" ${rule.formula_type === 'percentage' ? 'selected' : ''}>%</option>
                    </select>
                    <span class="editable editable-amount" data-field="value" data-id="${rule.id}" data-type="number">${formatValue(rule)}</span>
                </div>
            </td>
            <td>
                <select class="cell-select" data-field="applies_to" data-id="${rule.id}" style="font-size:.74rem">
                    <option value="BASICPAY" ${rule.applies_to === 'BASICPAY' ? 'selected' : ''}>Basic</option>
                    <option value="GROSSPAY" ${rule.applies_to === 'GROSSPAY' ? 'selected' : ''}>Gross</option>
                </select>
            </td>
            <td>
                <select class="tax-pill" data-field="tax_profile" data-id="${rule.id}" data-val="${rule.tax_profile}">
                    <option value="taxable" ${rule.tax_profile === 'taxable' ? 'selected' : ''}>PAYE+NAPSA</option>
                    <option value="napsa_only" ${rule.tax_profile === 'napsa_only' ? 'selected' : ''}>NAPSA only</option>
                    <option value="non_taxable" ${rule.tax_profile === 'non_taxable' ? 'selected' : ''}>Not taxed</option>
                </select>
            </td>
            <td>
                <div class="flags-row">
                    ${flagDot(rule, 'is_statutory', 'Statutory')}
                    ${flagDot(rule, 'is_recurring', 'Recurring')}
                    ${flagDot(rule, 'requires_assignment', 'Needs assignment')}
                    ${flagDot(rule, 'is_pensionable', 'Pensionable')}
                    ${flagDot(rule, 'show_on_payslip', 'Shown on payslip')}
                </div>
            </td>
            <td>
                <label class="toggle-switch">
                    <input type="checkbox" data-field="active" data-id="${rule.id}" ${rule.active ? 'checked' : ''}>
                    <span class="toggle-track"></span>
                </label>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <span class="save-flash" data-flash="${rule.id}">✓ saved</span>
                    <button class="btn-row-delete" data-delete="${rule.id}" title="Delete rule">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/>
                        </svg>
                    </button>
                </div>
            </td>
        `;
        return tr;
    }

    function flagDot(rule, field, label) {
        const on = !!rule[field];
        return `<label class="flag-toggle" title="${label}${on ? ' — on' : ' — off'}">
                    <input type="checkbox" data-field="${field}" data-id="${rule.id}" ${on ? 'checked' : ''}>
                    <span class="flag-dot"></span>
                </label>`;
    }

    function formatValue(rule) {
        const v = parseFloat(rule.value || 0);
        return v.toFixed(2);
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    /* ──────────────────────────────────────────────────────────────
       SAVE A SINGLE FIELD  (PATCH)
    ────────────────────────────────────────────────────────────── */
    function saveField(id, field, value) {
        const tr = tbody.querySelector(`tr[data-id="${id}"]`);
        if (tr) tr.classList.add('rule-saving');

        fetch(ENDPOINTS.update(id), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ [field]: value }),
        })
        .then(r => r.json())
        .then(data => {
            if (tr) tr.classList.remove('rule-saving');

            if (!data.success) {
                showToast(data.message || 'Could not save.', 'error');
                return;
            }

            const idx = allRules.findIndex(r => r.id == id);
            if (idx > -1) allRules[idx] = data.rule;

            if (field === 'active' && tr) {
                tr.classList.toggle('rule-inactive', !data.rule.active);
            }
            if (field === 'tax_profile' && tr) {
                const sel = tr.querySelector('select[data-field="tax_profile"]');
                if (sel) sel.dataset.val = data.rule.tax_profile;
            }

            flashSaved(id);
        })
        .catch(() => {
            if (tr) tr.classList.remove('rule-saving');
            showToast('Network error — change not saved.', 'error');
        });
    }

    function flashSaved(id) {
        const flash = tbody.querySelector(`[data-flash="${id}"]`);
        if (!flash) return;
        flash.classList.add('show');
        setTimeout(() => flash.classList.remove('show'), 1400);
    }

    /* ──────────────────────────────────────────────────────────────
       EVENT DELEGATION — click-to-edit text cells
    ────────────────────────────────────────────────────────────── */
    tbody.addEventListener('click', function (e) {

        const editableEl = e.target.closest('.editable');
        if (editableEl && !editableEl.classList.contains('editing')) {
            startEdit(editableEl);
            return;
        }

        const deleteBtn = e.target.closest('[data-delete]');
        if (deleteBtn) {
            confirmDelete(deleteBtn.dataset.delete);
        }
    });

    function startEdit(el) {
        const currentText = el.textContent.trim();
        const field  = el.dataset.field;
        const id     = el.dataset.id;
        const isNum  = el.dataset.type === 'number';

        el.classList.add('editing');
        const inputValue = (currentText === '—' || currentText === 'No category') ? '' : currentText;
        el.innerHTML = `<input type="${isNum ? 'number' : 'text'}" ${isNum ? 'step="0.01"' : ''} value="${inputValue.replace(/"/g, '&quot;')}">`;

        const input = el.querySelector('input');
        input.focus();
        input.select();

        function commit() {
            const newVal = input.value.trim();
            el.classList.remove('editing');

            const displayVal = newVal || (field === 'category' ? 'No category' : '—');
            el.textContent = isNum ? parseFloat(newVal || 0).toFixed(2) : displayVal;

            if (newVal !== currentText) {
                saveField(id, field, isNum ? parseFloat(newVal || 0) : newVal);
            }
        }

        input.addEventListener('blur', commit);
        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
            if (ev.key === 'Escape') {
                el.classList.remove('editing');
                el.textContent = currentText;
            }
        });
    }

    /* ──────────────────────────────────────────────────────────────
       SELECT / TOGGLE CHANGES
    ────────────────────────────────────────────────────────────── */
    tbody.addEventListener('change', function (e) {

        const field = e.target.dataset.field;
        const id    = e.target.dataset.id;
        if (!field || !id) return;

        let value;
        if (e.target.type === 'checkbox') {
            value = e.target.checked;
        } else {
            value = e.target.value;
        }

        saveField(id, field, value);
    });

    /* ──────────────────────────────────────────────────────────────
       DELETE
    ────────────────────────────────────────────────────────────── */
    function confirmDelete(id) {
        const tr = tbody.querySelector(`tr[data-id="${id}"]`);
        const name = tr?.querySelector('.rule-name-cell')?.textContent ?? 'this rule';

        if (!confirm(`Delete "${name}"? This can't be undone.`)) return;

        if (tr) tr.classList.add('rule-deleting');

        fetch(ENDPOINTS.destroy(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message || 'Could not delete.', 'error');
                if (tr) tr.classList.remove('rule-deleting');
                return;
            }
            allRules = allRules.filter(r => r.id != id);
            tr?.remove();
            showToast(data.message || 'Rule deleted.', 'success');
            render();
        })
        .catch(() => {
            showToast('Network error — could not delete.', 'error');
            if (tr) tr.classList.remove('rule-deleting');
        });
    }

    /* ──────────────────────────────────────────────────────────────
       TOOLBAR — search & filter
    ────────────────────────────────────────────────────────────── */
    searchInput.addEventListener('input', (e) => {
        searchTerm = e.target.value;
        render();
    });

    document.querySelectorAll('.rules-filter-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('.rules-filter-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            activeFilter = chip.dataset.filter;
            render();
        });
    });

    /* ──────────────────────────────────────────────────────────────
       ADD RULE PANEL
    ────────────────────────────────────────────────────────────── */
    const addPanel  = document.getElementById('addRulePanel');
    const toggleBtn = document.getElementById('toggleAddRule');
    const cancelBtn = document.getElementById('cancelAddRule');
    const saveBtn   = document.getElementById('saveNewRule');
    const addError  = document.getElementById('addRuleError');

    toggleBtn.addEventListener('click', () => {
        addPanel.classList.toggle('d-none');
        if (!addPanel.classList.contains('d-none')) {
            document.getElementById('newName').focus();
        }
    });
    cancelBtn.addEventListener('click', () => {
        addPanel.classList.add('d-none');
        clearAddForm();
    });

    function clearAddForm() {
        document.getElementById('newName').value = '';
        document.getElementById('newCode').value = '';
        document.getElementById('newCategory').value = '';
        document.getElementById('newValue').value = '';
        document.getElementById('newType').value = 'earning';
        document.getElementById('newFormulaType').value = 'fixed';
        document.getElementById('newAppliesTo').value = 'BASICPAY';
        document.getElementById('newTaxProfile').value = 'taxable';
        document.getElementById('newRecurring').checked = true;
        document.getElementById('newPayslip').checked = true;
        document.getElementById('newStatutory').checked = false;
        document.getElementById('newPensionable').checked = false;
        document.getElementById('newRequiresAssignment').checked = false;
        addError.textContent = '';
    }

    saveBtn.addEventListener('click', () => {
        const name  = document.getElementById('newName').value.trim();
        const value = document.getElementById('newValue').value;

        addError.textContent = '';
        if (!name)  { addError.textContent = 'Name is required.'; return; }
        if (value === '' || isNaN(value)) { addError.textContent = 'Enter a valid amount.'; return; }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Creating…';

        fetch(ENDPOINTS.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                name:                name,
                code:                document.getElementById('newCode').value.trim() || null,
                type:                document.getElementById('newType').value,
                category:            document.getElementById('newCategory').value.trim() || null,
                formula_type:        document.getElementById('newFormulaType').value,
                value:               parseFloat(value),
                applies_to:          document.getElementById('newAppliesTo').value,
                tax_profile:         document.getElementById('newTaxProfile').value,
                is_recurring:        document.getElementById('newRecurring').checked,
                show_on_payslip:     document.getElementById('newPayslip').checked,
                is_statutory:        document.getElementById('newStatutory').checked,
                is_pensionable:      document.getElementById('newPensionable').checked,
                requires_assignment: document.getElementById('newRequiresAssignment').checked,
            }),
        })
        .then(r => r.json())
        .then(data => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Create rule';

            if (!data.success) {
                addError.textContent = data.message || 'Could not create rule.';
                return;
            }

            allRules.push(data.rule);
            render();
            addPanel.classList.add('d-none');
            clearAddForm();
            showToast('Rule created.', 'success');
        })
        .catch(() => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Create rule';
            addError.textContent = 'Network error — could not create rule.';
        });
    });

    /* ──────────────────────────────────────────────────────────────
       TOAST  (falls back to console if no global showToast exists)
    ────────────────────────────────────────────────────────────── */
    function showToast(msg, type) {
        if (typeof window.showToast === 'function' && window.showToast !== showToast) {
            window.showToast(msg, type);
            return;
        }
        console.log(`[${type}] ${msg}`);
    }

    /* ──────────────────────────────────────────────────────────────
       INIT — load rules every time the modal opens
    ────────────────────────────────────────────────────────────── */
    document.getElementById('payrollRulesModal')
        .addEventListener('shown.bs.modal', loadRules);

})();
</script>