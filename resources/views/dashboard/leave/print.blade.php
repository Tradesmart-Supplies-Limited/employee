<!DOCTYPE html>
<html>
<head>
    <title>Leave Application</title>

    <style>


     @page {
            size: A4;
            margin: 10mm;
        }
/* ---- Paper ---- */
.lv-paper {
    background: #fff;
    border: 1px solid #c8c8c8;
    border-radius: 3px;
    overflow: hidden;
    max-width: 860px;
}

/* ---- Letterhead ---- */
.lv-header {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 20px 32px 16px;
    border-bottom: 4px solid #185FA5;
}

.lv-logo {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.lv-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.lv-logo-text {
    font-weight: 700;
    font-size: 15px;
    color: #185FA5;
}

.lv-company-name {
    font-size: 20px;
    font-weight: 700;
    color: #185FA5;
    margin-bottom: 3px;
}

.lv-company-meta {
    font-size: 11.5px;
    color: #777;
    line-height: 1.6;
}

/* ---- Form title ---- */
.lv-form-title {
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-decoration: underline;
    padding: 12px 32px;
    border-bottom: 1px solid #ddd;
}

/* ---- Sections ---- */
.lv-section {
    padding: 4px 32px 22px;
    border-bottom: 1px solid #ccc;
}

.lv-section-shaded {
    background: #fafaf8;
}

.lv-section-heading {
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    background: #3a3a3a;
    padding: 5px 12px;
    margin: 0 -32px 18px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.lv-heading-note {
    font-weight: 400;
    font-size: 11px;
    opacity: 0.8;
    margin-left: 6px;
}

/* ---- Field display ---- */
.lv-field {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.lv-field-label {
    font-size: 11px;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.lv-hint {
    font-weight: 400;
    font-size: 10.5px;
    color: #aaa;
    text-transform: none;
    letter-spacing: 0;
}

.lv-field-value {
    font-size: 13.5px;
    color: #1a1a1a;
    border-bottom: 1px dotted #ccc;
    padding: 3px 0 4px;
    min-height: 10px;
}

.lv-field-value--block {
    border: 1px solid #e0e0e0;
    border-radius: 3px;
    padding: 4px 5px;
    font-size: 13px;
    line-height: 1.6;
    white-space: pre-wrap;
    background: #fefefe;
}

/* ---- Grid layouts ---- */
.lv-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 24px;
}

.lv-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px 24px;
}

.lv-sig-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 24px;
}

/* ---- Leave type grid ---- */
.lv-leave-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 1px solid #ccc;
    border-radius: 3px;
    overflow: hidden;
}

.lv-leave-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 8px;
    border-bottom: 1px solid #e0e0e0;
    border-right: 1px solid #e0e0e0;
    font-size: 8px;
    color: #555;
}

.lv-leave-cell:nth-child(even) {
    border-right: none;
}

.lv-leave-cell:nth-last-child(-n+2) {
    border-bottom: none;
}

.lv-leave-cell--checked {
    background: #E6F1FB;
    color: #0C447C;
    font-weight: 600;
}

.lv-leave-num {
    font-size: 7px;
    color: #bbb;
    width: 10px;
    flex-shrink: 0;
}

.lv-leave-mark {
    font-size: 7px;
    font-weight: 700;
    color: #185FA5;
    width: 8px;
    flex-shrink: 0;
    text-align: center;
}

/* ---- Document button ---- */
.lv-doc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12.5px;
    padding: 5px 14px;
    border: 1px solid #b8d6f5;
    border-radius: 5px;
    color: #185FA5;
    background: #f0f7ff;
    text-decoration: none;
    margin-top: 4px;
    transition: background .12s;
}

.lv-doc-btn:hover {
    background: #e0eeff;
    color: #0C447C;
}

/* ---- Signature lines ---- */
.lv-sig-line {
    border-bottom: 1px dotted #aaa;
    min-height: 20px;
    margin-top: 4px;
}

/* ---- Active inputs ---- */
.lv-input {
    border: none;
    border-bottom: 1.5px solid #aaa;
    border-radius: 0;
    padding: 5px 2px;
    font-size: 13px;
    background: transparent;
    width: 100%;
    color: #1a1a1a;
    outline: none;
    box-shadow: none !important;
    transition: border-color .15s;
}

.lv-input:focus {
    border-bottom-color: #185FA5;
}

.lv-textarea {
    border: 1px solid #ccc;
    border-radius: 3px;
    padding: 8px 10px;
    font-size: 13px;
    width: 100%;
    resize: vertical;
    color: #1a1a1a;
    outline: none;
    font-family: inherit;
    box-shadow: none !important;
    transition: border-color .15s;
    background: #fff;
}

.lv-textarea:focus {
    border-color: #185FA5;
}

/* ---- Approval radio ---- */
.lv-approval-row {
    display: flex;
    gap: 32px;
    margin-top: 8px;
}

.lv-approval-option {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 500;
    color: #333;
    cursor: pointer;
    margin: 0;
}

.lv-radio {
    accent-color: #185FA5;
    width: 15px;
    height: 15px;
    cursor: pointer;
}

/* ---- Footer note ---- */
.lv-footer-note {
    font-size: 11.5px;
    color: #555;
    line-height: 1.7;
    padding: 12px 32px;
    border-top: 3px solid #185FA5;
    background: #f2f8ff;
    font-style: italic;
}

/* ---- Print ---- */
@media print {
    .lv-paper {
        border: none;
        max-width: 100%;
    }

    .btn,
    .alert {
        display: none !important;
    }

    .lv-section-shaded {
        background: #fafaf8 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .lv-section-heading {
        background: #3a3a3a !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* ---- Responsive ---- */
@media (max-width: 640px) {
    .lv-header {
        flex-direction: column;
        text-align: center;
        padding: 16px;
    }

    .lv-section {
        padding: 4px 16px 18px;
    }

    .lv-section-heading {
        margin: 0 -16px 16px;
    }

    .lv-footer-note {
        padding: 12px 16px;
    }

    .lv-row-2,
    .lv-row-3,
    .lv-sig-row {
        grid-template-columns: 1fr;
    }

    .lv-leave-grid {
        grid-template-columns: 1fr;
    }

    .lv-leave-cell {
        border-right: 1px solid #e0e0e0 !important;
    }

    .lv-leave-cell:last-child {
        border-bottom: none;
    }
}
</style>

</head>

<body>

{{-- ===== PAPER ===== --}}
<div class="lv-paper">

    {{-- LETTERHEAD --}}
    <div class="lv-header">
        <div class="lv-logo">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Logo"
                onerror="this.parentElement.innerHTML='<div class=\'lv-logo-text\'>TS</div>'">
        </div>
        <div>
            <div class="lv-company-name">Tradesmart Supplies Limited</div>
            <div class="lv-company-meta">
                Head Quarters, Stand No 36, Emporium, 3rd Floor, Buteko Avenue, Ndola, Zambia <br>
                +243 999 000 005 &nbsp;·&nbsp; +260 968 668 012 &nbsp;·&nbsp; +256 780 262 194
                &nbsp;·&nbsp; andrew@tradesmartzm.com &nbsp;·&nbsp; info@tradesmartzm.com
            </div>
        </div>
    </div>

    <div class="lv-form-title text-primary">LEAVE REQUEST FORM</div>

    {{-- =====================================================
         SECTION 1 — REQUESTOR (read-only display)
    ====================================================== --}}
    <div class="lv-section">
        <div class="lv-section-heading">To be completed by requestor</div>

        <div class="lv-row-2">
            <div class="lv-field">
                <div class="lv-field-label">Name</div>
                <div class="lv-field-value">{{ $leave->employee_name }}</div>
            </div>
            <div class="lv-field">
                <div class="lv-field-label">Employee No.</div>
                <div class="lv-field-value">{{ $leave->employee_number ?? '—' }}</div>
            </div>
        </div>

        <div class="lv-row-2 mt-3">
            <div class="lv-field">
                <div class="lv-field-label">Position</div>
                <div class="lv-field-value">{{ $leave->position ?? '—' }}</div>
            </div>
            <div class="lv-field">
                <div class="lv-field-label">Email</div>
                <div class="lv-field-value">{{ $leave->email }}</div>
            </div>
        </div>

        {{-- Leave type grid --}}
        <div class="lv-field mt-2">
            <div class="lv-field-label">Leave applied for</div>
            <div class="lv-leave-grid mt-1">
                @php
                $leaveTypes = [
                1 => 'Annual',
                2 => 'Sick',
                3 => 'Maternity',
                4 => 'Paternal',
                5 => 'Compassionate',
                6 => 'Unpaid',
                7 => 'Other',
                8 => 'Study',
                ];
                @endphp
                @foreach($leaveTypes as $num => $type)
                <div class="lv-leave-cell {{ $leave->leave_type === $type ? 'lv-leave-cell--checked' : '' }}">
                    <span class="lv-leave-num">{{ $num }}.</span>
                    <span class="lv-leave-mark">{{ $leave->leave_type === $type ? '✕' : '' }}</span>
                    <span>{{ $type }}</span>
                </div>
                @endforeach
            </div>
        </div>

        @if($leave->other_leave_type)
        <div class="lv-field mt-3">
            <div class="lv-field-label">If other, specify</div>
            <div class="lv-field-value">{{ $leave->other_leave_type }}</div>
        </div>
        @endif

        <div class="lv-row-3 mt-3">
            <div class="lv-field">
                <div class="lv-field-label">From</div>
                <div class="lv-field-value">{{ \Carbon\Carbon::parse($leave->date_from)->format('M d, Y') }}</div>
            </div>
            <div class="lv-field">
                <div class="lv-field-label">To</div>
                <div class="lv-field-value">{{ \Carbon\Carbon::parse($leave->date_to)->format('M d, Y') }}</div>
            </div>
            <div class="lv-field">
                <div class="lv-field-label">Return Date</div>
                <div class="lv-field-value">
                    {{ $leave->return_date ? \Carbon\Carbon::parse($leave->return_date)->format('M d, Y') : '—' }}
                </div>
            </div>
        </div>

        <div class="lv-row-2 mt-3">
            <div class="lv-field">
                <div class="lv-field-label">Total Days Requested</div>
                <div class="lv-field-value fw-semibold">{{ $leave->total_days }}
                    {{ $leave->total_days == 1 ? 'day' : 'days' }}</div>
            </div>
            <div class="lv-field">
                <div class="lv-field-label">Date Submitted</div>
                <div class="lv-field-value">
                    {{ $leave->applicant_signature_date ? \Carbon\Carbon::parse($leave->applicant_signature_date)->format('M d, Y') : $leave->created_at->format('M d, Y') }}
                </div>
            </div>
        </div>

        @if($leave->reason)
        <div class="lv-field mt-3">
            <div class="lv-field-label">Reason <span class="lv-hint">(for Compassionate, Unpaid, Other, Study)</span>
            </div>
            <div class="lv-field-value lv-field-value--block">{{ $leave->reason }}</div>
        </div>
        @endif

        @if($leave->late_application_reason)
        <div class="lv-field mt-3">
            <div class="lv-field-label">Late application explanation</div>
            <div class="lv-field-value lv-field-value--block">{{ $leave->late_application_reason }}</div>
        </div>
        @endif

        @if($leave->supporting_document)
        <div class="lv-field mt-3">
            <div class="lv-field-label">Supporting Document</div>
            <a href="{{ asset('storage/' . $leave->supporting_document) }}" target="_blank" class="lv-doc-btn">
                <i class="bi bi-file-earmark-text"></i>
                View Attached Document
                <i class="bi bi-box-arrow-up-right ms-1" style="font-size:11px"></i>
            </a>
        </div>
        @endif

        <div class="lv-sig-row mt-3">
            <div class="lv-field">
                <div class="lv-field-label">Applicant's Signature</div>
                <div class="lv-sig-line"></div>
            </div>
            <div class="lv-field">
                <div class="lv-field-label">Date</div>
                <div class="lv-sig-line"></div>
            </div>
        </div>

    </div>{{-- end section 1 --}}


    {{-- =====================================================
         SECTION 2 — SUPERVISOR (active update form)
    ====================================================== --}}
    <form method="POST" action="{{ route('leave.supervisor.update', $leave->id) }}" id="supervisorForm">
        @csrf
        @method('PATCH')

        <div class="lv-section lv-section-shaded">
            <div class="lv-section-heading">To be completed by supervisor</div>

            <div class="lv-approval-row">
                <label class="lv-approval-option">
                    <input type="radio" name="supervisor_decision" value="Approved" class="lv-radio"
                        {{ $leave->supervisor_decision === 'Approved' ? 'checked' : '' }}>
                    <span>Approved</span>
                </label>
                <label class="lv-approval-option">
                    <input type="radio" name="supervisor_decision" value="Rejected" class="lv-radio"
                        {{ $leave->supervisor_decision === 'Rejected' ? 'checked' : '' }}>
                    <span>Rejected</span>
                </label>
            </div>

            <div class="lv-field mt-3">
                <label class="lv-field-label" for="supervisor_comments">Reason(s) for rejection</label>
                <textarea name="supervisor_comments" id="supervisor_comments" rows="1"
                    class="lv-textarea">{{ old('supervisor_decision', $leave->supervisor_decision) }} - {{ old('supervisor_comments', $leave->supervisor_comments) }}</textarea>
            </div>

            <div class="lv-row-2 mt-3">
                <div class="lv-field">
                    <label class="lv-field-label" for="supervisor_name">Name</label>
                    <input type="text" name="supervisor_name" id="supervisor_name" class="lv-input"
                        value="{{ old('supervisor_name', $leave->supervisor_name) }}">
                </div>
                <div class="lv-field">
                    <label class="lv-field-label" for="supervisor_position">Position</label>
                    <input type="text" name="supervisor_position" id="supervisor_position" class="lv-input"
                        value="{{ old('supervisor_position', $leave->supervisor_position) }}">
                </div>
            </div>

            <div class="lv-sig-row mt-3">
                <div class="lv-field">
                    <div class="lv-field-label">Signature</div>
                    <div class="lv-sig-line"></div>
                </div>
                <div class="lv-field">
                    <div class="lv-field-label">Date</div>
                    <div class="lv-sig-line"></div>
                </div>
            </div>


        </div>

    </form>{{-- end supervisor form --}}


    {{-- =====================================================
         SECTION 3 — HR DEPARTMENT (active update form)
    ====================================================== --}}
    <form method="POST" action="{{ route('leave.hr.update', $leave->id) }}" id="hrForm">
        @csrf
        @method('PATCH')

        <div class="lv-section lv-section-shaded">
            <div class="lv-section-heading">
                To be completed by HR Department
                <span class="lv-heading-note">(Yearly Annual Leave entitlement — 24 Days)</span>
            </div>

            <div class="lv-row-2 mt-2">
                <div class="lv-field">
                    <label class="lv-field-label" for="days_accrued">Days accrued in this calendar year</label>
                    <input type="number" name="days_accrued" id="days_accrued" class="lv-input" min="0"
                        value="{{ old('days_accrued', $leave->days_accrued) }}" oninput="calcBalance()">
                </div>
                <div class="lv-field">
                    <label class="lv-field-label" for="days_available">Number of days available</label>
                    <input type="number" name="days_available" id="days_available" class="lv-input" min="0"
                        value="{{ old('days_available', $leave->days_available) }}" oninput="calcBalance()">
                </div>
            </div>

            <div class="lv-row-2 mt-3">
                <div class="lv-field">
                    <label class="lv-field-label">Less: days requested on this form</label>
                    <input type="number" name="days_requested_hr" class="lv-input" min="0"
                        value="{{ old('days_requested_hr', $leave->days_requested_hr ?? $leave->total_days) }}"
                        oninput="calcBalance()">
                </div>
                <div class="lv-field">
                    <label class="lv-field-label" for="days_balance">Balance to be carried forward</label>
                    <input type="number" name="days_balance" id="days_balance" class="lv-input" min="0"
                        value="{{ old('days_balance', $leave->days_balance) }}" readonly style="background:#f5f9ff">
                </div>
            </div>

            <div class="lv-row-2 mt-3">
                <div class="lv-field">
                    <label class="lv-field-label" for="hr_name">Name</label>
                    <input type="text" name="hr_name" id="hr_name" class="lv-input"
                        value="{{ old('hr_name', $leave->hr_name) }}">
                </div>
                <div class="lv-field">
                    <label class="lv-field-label" for="hr_position">Position</label>
                    <input type="text" name="hr_position" id="hr_position" class="lv-input"
                        value="{{ old('hr_position', $leave->hr_position) }}">
                </div>
            </div>

            <div class="lv-sig-row mt-3">
                <div class="lv-field">
                    <div class="lv-field-label">Signature</div>
                    <div class="lv-sig-line"></div>
                </div>
                <div class="lv-field">
                    <div class="lv-field-label">Date</div>
                    <div class="lv-sig-line"></div>
                </div>
            </div>

    

        </div>

    </form>{{-- end HR form --}}


    {{-- FOOTER NOTE --}}
    <div class="lv-footer-note">
        <i class="bi bi-info-circle me-1"></i>
        Unless in emergency situations, employees are required to submit leave applications not less than
        <strong>five working days</strong> prior to the proposed date of commencement of leave.
    </div>

</div>{{-- end lv-paper --}}


{{-- ===== SCRIPTS ===== --}}
<script>
function calcBalance() {
    let accrued = parseInt(document.getElementById('days_accrued')?.value || 0);
    let available = parseInt(document.getElementById('days_available')?.value || 0);
    let requested = parseInt(document.getElementsByName('days_requested_hr')[0]?.value || 0);

    let balance = (accrued + available) - requested;

    document.getElementById('days_balance').value = balance;
}
calcBalance();

  window.onload = function () {
        window.print();
    }
</script>


</body>
</html>