{{--
    _adjustment-modal.blade.php
    Include this in your show.blade.php:  @include('...._adjustment-modal')
--}}

<div class="modal fade" id="adjustmentModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content adj-modal">

            {{-- ════════════════════════════════════════════════════════
                 HEADER
            ════════════════════════════════════════════════════════ --}}
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Payroll adjustments</h5>
                    <p class="text-muted small mb-0">Add one-off earnings or deductions to an employee's payslip</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">

                {{-- ════════════════════════════════════════════════════
                     EMPLOYEE PICKER
                ════════════════════════════════════════════════════ --}}
                <div class="adj-picker-bar">

                    <div class="adj-picker" id="employeePicker">

                        <div class="adj-picker-control" id="employeePickerControl" tabindex="0">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <span class="adj-picker-placeholder" id="employeePickerLabel">Select an employee…</span>
                            <svg class="adj-picker-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </div>

                        <select id="employeeSelector" class="d-none">
                            <option value="">Select Employee</option>
                            @foreach($run->payrolls as $payroll)
                                <option value="{{ $payroll->id }}"
                                        data-name="{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}"
                                        data-empid="{{ $payroll->employee->employee_id }}">
                                    {{ $payroll->employee->employee_id }} — {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="adj-picker-dropdown d-none" id="employeePickerDropdown">
                            <div class="adj-picker-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                                </svg>
                                <input type="text" id="employeePickerSearch" placeholder="Search by name or ID" autocomplete="off">
                            </div>
                            <div class="adj-picker-list" id="employeePickerList">
                                {{-- populated by JS --}}
                            </div>
                        </div>

                    </div>

                </div>

                {{-- ════════════════════════════════════════════════════
                     AJAX CONTENT — replaced on every load/reload
                ════════════════════════════════════════════════════ --}}
                <div id="employeePayrollSummary" class="p-5">
                    <div class="adj-empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <path d="M14 2v6h6"/>
                            <path d="M9 13h6M9 17h6"/>
                        </svg>
                        <p class="mb-0">Select an employee to view their payslip</p>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════
     STYLES — shares tokens with the rules modal for visual consistency
════════════════════════════════════════════════════════════════ --}}
<style>
.adj-modal {
    --am-border: #e8e9ec;
    --am-text: #1a1d23;
    --am-muted: #75798a;
    --am-bg-subtle: #f7f8fa;
    --am-accent: #2f6f4e;
    --am-accent-soft: #e7f3ec;
    --am-danger: #c4453a;
    --am-danger-soft: #fbeae8;
    --am-warn: #b8842a;
    --am-warn-soft: #fbf2e3;
    --am-radius: 8px;
    font-size: 0.875rem;
    color: var(--am-text);
}

.adj-modal .modal-header { border-bottom: 1px solid var(--am-border); padding: 1.1rem 1.4rem; }
.adj-modal .modal-title { font-weight: 600; font-size: 1.02rem; letter-spacing: -0.01em; }

/* ── Employee picker bar ─────────────────────────────────────────── */
.adj-picker-bar {
    padding: 1rem 1.4rem;
    border-bottom: 1px solid var(--am-border);
    background: #fff;
}

.adj-picker { position: relative; max-width: 360px; }

.adj-picker-control {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    border: 1px solid var(--am-border);
    border-radius: var(--am-radius);
    background: var(--am-bg-subtle);
    padding: 0.6rem 0.8rem;
    cursor: pointer;
    color: var(--am-muted);
    transition: border-color .12s ease;
}
.adj-picker-control:hover,
.adj-picker-control:focus { border-color: #c7cad1; outline: none; }
.adj-picker-control.open { border-color: var(--am-accent); box-shadow: 0 0 0 3px var(--am-accent-soft); }

.adj-picker-placeholder { flex: 1; font-size: 0.85rem; color: var(--am-muted); }
.adj-picker-placeholder.has-value { color: var(--am-text); font-weight: 500; }
.adj-picker-chevron { margin-left: auto; flex-shrink: 0; transition: transform .12s ease; }
.adj-picker-control.open .adj-picker-chevron { transform: rotate(180deg); }

.adj-picker-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    min-width: 320px;
    background: #fff;
    border: 1px solid var(--am-border);
    border-radius: var(--am-radius);
    box-shadow: 0 8px 24px rgba(20, 22, 28, 0.12);
    z-index: 20;
    overflow: hidden;
}

.adj-picker-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 0.8rem;
    border-bottom: 1px solid var(--am-border);
    color: var(--am-muted);
}
.adj-picker-search input {
    border: none;
    outline: none;
    background: transparent;
    width: 100%;
    font-size: 0.85rem;
    color: var(--am-text);
}

.adj-picker-list { max-height: 260px; overflow-y: auto; padding: 0.3rem; }

.adj-picker-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.5rem 0.6rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.83rem;
}
.adj-picker-item:hover { background: var(--am-bg-subtle); }
.adj-picker-item.selected { background: var(--am-accent-soft); }

.adj-picker-avatar {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: var(--am-text);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.68rem;
    font-weight: 600;
    flex-shrink: 0;
}

.adj-picker-name { font-weight: 500; }
.adj-picker-id { color: var(--am-muted); font-size: 0.75rem; margin-left: auto; font-family: 'SF Mono', Consolas, monospace; }

.adj-picker-no-results { padding: 1.2rem; text-align: center; color: var(--am-muted); font-size: 0.82rem; }

/* ── Empty state ──────────────────────────────────────────────────── */
.adj-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.7rem;
    padding: 3.5rem 1rem;
    color: var(--am-muted);
    text-align: center;
}
.adj-empty-state svg { color: #c7cad1; }

/* ── Loading state ────────────────────────────────────────────────── */
.adj-loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 3.5rem 1rem;
    color: var(--am-muted);
}
.adj-spinner {
    width: 16px; height: 16px;
    border: 2px solid var(--am-border);
    border-top-color: var(--am-accent);
    border-radius: 50%;
    animation: adjSpin .6s linear infinite;
}
@keyframes adjSpin { to { transform: rotate(360deg); } }
</style>

{{-- ════════════════════════════════════════════════════════════════
     SCRIPT — searchable employee picker
════════════════════════════════════════════════════════════════ --}}
<script>
(function () {

    const nativeSelect = document.getElementById('employeeSelector');
    const control       = document.getElementById('employeePickerControl');
    const label         = document.getElementById('employeePickerLabel');
    const dropdown      = document.getElementById('employeePickerDropdown');
    const listEl        = document.getElementById('employeePickerList');
    const searchInput   = document.getElementById('employeePickerSearch');

    // Build option list from the (hidden) native <select>
    const employees = Array.from(nativeSelect.options)
        .filter(o => o.value !== '')
        .map(o => ({
            id:    o.value,
            name:  o.dataset.name,
            empId: o.dataset.empid,
        }));

    function initials(name) {
        return name.split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    }

    function renderList(filter = '') {
        const q = filter.trim().toLowerCase();
        const filtered = employees.filter(e =>
            e.name.toLowerCase().includes(q) || e.empId.toLowerCase().includes(q)
        );

        if (filtered.length === 0) {
            listEl.innerHTML = '<div class="adj-picker-no-results">No employees match your search.</div>';
            return;
        }

        listEl.innerHTML = filtered.map(e => `
            <div class="adj-picker-item" data-id="${e.id}">
                <span class="adj-picker-avatar">${initials(e.name)}</span>
                <span class="adj-picker-name">${e.name}</span>
                <span class="adj-picker-id">${e.empId}</span>
            </div>
        `).join('');
    }

    function openDropdown() {
        renderList(searchInput.value);
        dropdown.classList.remove('d-none');
        control.classList.add('open');
        searchInput.focus();
    }

    function closeDropdown() {
        dropdown.classList.add('d-none');
        control.classList.remove('open');
    }

    control.addEventListener('click', () => {
        dropdown.classList.contains('d-none') ? openDropdown() : closeDropdown();
    });

    control.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDropdown(); }
    });

    searchInput.addEventListener('input', () => renderList(searchInput.value));

    listEl.addEventListener('click', (e) => {
        const item = e.target.closest('.adj-picker-item');
        if (!item) return;

        const emp = employees.find(x => x.id === item.dataset.id);
        if (!emp) return;

        label.textContent = `${emp.empId} — ${emp.name}`;
        label.classList.add('has-value');
        nativeSelect.value = emp.id;

        closeDropdown();

        // Call the existing global function used elsewhere in the app
        if (typeof reloadEmployeeSummary === 'function') {
            reloadEmployeeSummary(emp.id);
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!document.getElementById('employeePicker').contains(e.target)) {
            closeDropdown();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDropdown();
    });

})();
</script>