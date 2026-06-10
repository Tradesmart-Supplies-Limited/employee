<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
    body {
        background: #f0f2f5;
        font-size: 14px;
        min-height: 100vh;
        padding: 32px 16px 48px;
    }

    /* ---- Paper ---- */
    .lf-paper {
        max-width: 780px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #c8c8c8;
        border-radius: 3px;
        overflow: hidden;
    }

    /* ---- Letterhead ---- */
    .lf-header {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px 32px 16px;
        border-bottom: 4px solid #185FA5;
    }

    .lf-logo {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
    }

    .lf-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .lf-logo-fallback {
        font-size: 11px;
        font-weight: 700;
        color: #185FA5;
        text-align: center;
        line-height: 1.3;
        padding: 6px;
    }

    .lf-company-name {
        font-size: 21px;
        font-weight: 700;
        color: #185FA5;
        letter-spacing: 0.02em;
        margin-bottom: 3px;
    }

    .lf-company-meta {
        font-size: 11.5px;
        color: #777;
        line-height: 1.7;
    }

    /* ---- Form title ---- */
    .lf-form-title {
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-decoration: underline;
        text-transform: uppercase;
        padding: 13px 32px;
        border-bottom: 1px solid #ddd;
        background: #fff;
    }

    /* ---- Section ---- */
    .lf-section {
        padding: 6px 32px 22px;
    }

    .lf-section-heading {
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: #3a3a3a;
        padding: 5px 12px;
        margin: 0 -32px 18px;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    /* ---- Labels ---- */
    .lf-label {
        font-size: 11.5px;
        font-weight: 600;
        color: #444;
        margin-bottom: 4px;
        letter-spacing: 0.01em;
    }

    .lf-hint {
        font-weight: 400;
        color: #999;
        font-size: 11px;
    }

    /* ---- Inputs — underline style ---- */
    .lf-input {
        border: none;
        border-bottom: 1.5px solid #aaa;
        border-radius: 0;
        padding: 5px 2px;
        font-size: 13px;
        background: transparent;
        width: 100%;
        color: #1a1a1a;
        transition: border-color .15s;
        outline: none;
        box-shadow: none !important;
    }

    .lf-input:focus {
        border-bottom-color: #185FA5;
        background: transparent;
    }

    .lf-textarea {
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 8px 10px;
        font-size: 13px;
        width: 100%;
        resize: vertical;
        color: #1a1a1a;
        outline: none;
        font-family: inherit;
        transition: border-color .15s;
        box-shadow: none !important;
    }

    .lf-textarea:focus {
        border-color: #185FA5;
    }

    /* ---- Leave type grid ---- */
    .lf-leave-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1px solid #ccc;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 6px;
    }

    .lf-leave-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        border-bottom: 1px solid #e0e0e0;
        cursor: pointer;
        font-size: 13px;
        color: #1a1a1a;
        margin: 0;
        font-weight: 400;
        transition: background .1s;
        border-right: 1px solid #e0e0e0;
    }

    .lf-leave-option:nth-child(even) {
        border-right: none;
    }

    .lf-leave-option:nth-last-child(-n+2) {
        border-bottom: none;
    }

    .lf-leave-option:has(input:checked) {
        background: #E6F1FB;
    }

    .lf-leave-option:hover {
        background: #f5f9ff;
    }

    .lf-leave-num {
        font-size: 11px;
        color: #aaa;
        width: 16px;
        flex-shrink: 0;
    }

    .lf-leave-radio {
        accent-color: #185FA5;
        width: 15px;
        height: 15px;
        flex-shrink: 0;
        cursor: pointer;
    }

    /* ---- File input ---- */
    .lf-file-wrap {
        border: 1.5px dashed #ccc;
        border-radius: 5px;
        padding: 14px 16px;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s, background .15s;
    }

    .lf-file-wrap:hover {
        border-color: #185FA5;
        background: #f5f9ff;
    }

    .lf-file-wrap input[type="file"] {
        display: none;
    }

    .lf-file-label {
        font-size: 12.5px;
        color: #666;
        cursor: pointer;
        display: block;
    }

    .lf-file-label i {
        font-size: 22px;
        color: #185FA5;
        display: block;
        margin-bottom: 5px;
    }

    .lf-file-name {
        font-size: 12px;
        color: #185FA5;
        margin-top: 6px;
        display: none;
    }

    /* ---- Dates row ---- */
    .lf-dates-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }

    /* ---- Other leave type (conditionally shown) ---- */
    #otherWrap {
        display: none;
    }

    /* ---- Footer note ---- */
    .lf-footer-note {
        font-size: 11.5px;
        color: #555;
        line-height: 1.7;
        padding: 12px 32px;
        border-top: 3px solid #185FA5;
        background: #f2f8ff;
        font-style: italic;
    }

    /* ---- Submit bar ---- */
    .lf-submit-bar {
        padding: 16px 32px;
        background: #fafafa;
        border-top: 1px solid #e8e8e8;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* ---- Alert ---- */
    .lf-alert {
        margin: 0 32px 0;
        border-radius: 4px;
        font-size: 13px;
    }

    /* ---- Responsive ---- */
    @media (max-width: 600px) {
        .lf-header {
            flex-direction: column;
            text-align: center;
            padding: 16px;
        }

        .lf-section {
            padding: 6px 16px 18px;
        }

        .lf-section-heading {
            margin: 0 -16px 16px;
        }

        .lf-footer-note {
            padding: 12px 16px;
        }

        .lf-submit-bar {
            padding: 14px 16px;
        }

        .lf-leave-grid {
            grid-template-columns: 1fr;
        }

        .lf-leave-option {
            border-right: 1px solid #e0e0e0 !important;
        }

        .lf-leave-option:last-child {
            border-bottom: none;
        }

        .lf-dates-row {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        body {
            background: #fff;
            padding: 0;
        }

        .lf-submit-bar {
            display: none;
        }

        .lf-paper {
            border: none;
        }
    }
    </style>
</head>

<body>

    <div class="lf-paper">

        {{-- ===== LETTERHEAD ===== --}}
        <div class="lf-header">
            <div class="lf-logo">
                <img src="http://misc.tradesmartzm.com/logo.png" alt="Logo"
                    onerror="this.parentElement.innerHTML='<div class=\'lf-logo-fallback\'>Trade<br>Smart</div>'">
            </div>
            <div>
                <div class="lf-company-name">Tradesmart Supplies Limited</div>
                <div class="lf-company-meta">HR - Leave Application System
                </div>
            </div>
        </div>

        {{-- ===== TITLE ===== --}}
        <div class="lf-form-title">Leave Request Form</div>

        {{-- ===== SUCCESS / ERROR ALERTS ===== --}}
        @if(session('success'))
        <div class="lf-alert alert alert-success mt-3 mx-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="lf-alert alert alert-danger mt-3 mx-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-1 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- ===== FORM ===== --}}
        <form method="POST" action="{{ route('leave.store') }}" enctype="multipart/form-data" id="leaveForm">

            @csrf

            <div class="lf-section">

                <div class="lf-section-heading">To be completed by requestor</div>

                {{-- Row 1: Name + Employee No --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <div class="lf-label">Name <span class="text-danger">*</span></div>
                        <input type="text" name="employee_name"
                            class="lf-input @error('employee_name') is-invalid @enderror"
                            value="{{ old('employee_name') }}" required>
                        @error('employee_name')
                        <div class="invalid-feedback d-block" style="font-size:11.5px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-5">
                        <div class="lf-label">Employee No.</div>
                        <input type="text" name="employee_number" class="lf-input" value="{{ old('employee_number') }}">
                    </div>
                </div>

                {{-- Row 2: Position + Email --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="lf-label">Position</div>
                        <input type="text" name="position" class="lf-input" value="{{ old('position') }}">
                    </div>
                    <div class="col-md-6">
                        <div class="lf-label">Email Address <span class="text-danger">*</span></div>
                        <input type="email" name="email" class="lf-input @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" required>
                        @error('email')
                        <div class="invalid-feedback d-block" style="font-size:11.5px">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Leave type --}}
                <div class="mb-3">
                    <div class="lf-label">
                        Leave applied for <span class="text-danger">*</span>
                        <span class="lf-hint">&nbsp;(select the appropriate type)</span>
                    </div>

                    <div class="lf-leave-grid">
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
                        <label class="lf-leave-option">
                            <input type="radio" name="leave_type" value="{{ $type }}" class="lf-leave-radio"
                                {{ old('leave_type') === $type ? 'checked' : '' }} required>
                            <span class="lf-leave-num">{{ $num }}.</span>
                            <span>{{ $type }}</span>
                        </label>
                        @endforeach
                    </div>

                    @error('leave_type')
                    <div class="text-danger mt-1" style="font-size:11.5px">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Other — shown only when "Other" is selected --}}
                <div class="mb-4" id="otherWrap">
                    <div class="lf-label">If <strong>other</strong>, specify</div>
                    <input type="text" name="other_leave_type" id="otherInput" class="lf-input" style="max-width: 340px"
                        value="{{ old('other_leave_type') }}">
                </div>

                {{-- Dates --}}
                <div class="lf-dates-row mb-4">
                    <div>
                        <div class="lf-label">From <span class="text-danger">*</span></div>
                        <input type="date" name="date_from" id="dateFrom"
                            class="lf-input @error('date_from') is-invalid @enderror" value="{{ old('date_from') }}"
                            required>
                        @error('date_from')
                        <div class="invalid-feedback d-block" style="font-size:11.5px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <div class="lf-label">To <span class="text-danger">*</span></div>
                        <input type="date" name="date_to" id="dateTo"
                            class="lf-input @error('date_to') is-invalid @enderror" value="{{ old('date_to') }}"
                            required>
                        @error('date_to')
                        <div class="invalid-feedback d-block" style="font-size:11.5px">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <div class="lf-label">Return Date</div>
                        <input type="date" name="return_date" id="returnDate" class="lf-input"
                            value="{{ old('return_date') }}">
                    </div>
                </div>

                {{-- Days summary (read-only, display only — not submitted) --}}
                <div class="mb-4" id="daysSummaryWrap" style="display:none">
                    <div
                        style="background:#f0f7ff; border:1px solid #b8d6f5; border-radius:5px; padding:10px 16px; font-size:13px; display:flex; align-items:center; gap:16px;">
                        <i class="bi bi-calendar-check text-primary" style="font-size:18px"></i>
                        <span>Total days requested: <strong id="daysSummary">—</strong></span>
                    </div>
                </div>

                {{-- Reason --}}
                <div class="mb-3">
                    <div class="lf-label">
                        Reason
                        <span class="lf-hint">&nbsp;(required for Compassionate, Unpaid, Other &amp; Study leave)</span>
                    </div>
                    <textarea name="reason" rows="3" class="lf-textarea">{{ old('reason') }}</textarea>
                </div>

                {{-- Late application --}}
                <div class="mb-4">
                    <div class="lf-label">If leave is requested less than 5 working days in advance, please explain
                        details</div>
                    <textarea name="late_application_reason" rows="3"
                        class="lf-textarea">{{ old('late_application_reason') }}</textarea>
                </div>

                {{-- Supporting document --}}
                <div class="mb-2">

                    <div class="lf-label">
                        Supporting Document
                        <span class="lf-hint">&nbsp;pdf, jpg, png, doc, docx — max 5 MB</span>
                    </div>

                    <div class="lf-file-wrap">

                        <input type="file" name="supporting_document" id="supportingDoc"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="showFileName(this)">

                        <label class="lf-file-label" for="supportingDoc">
                            <i class="bi bi-cloud-upload"></i>
                            Click to upload a supporting document
                        </label>

                        <div class="lf-file-name" id="fileNameDisplay">
                            <i class="bi bi-paperclip"></i>
                            <span id="fileNameText"></span>
                        </div>

                    </div>

                    @error('supporting_document')
                    <div class="text-danger mt-1" style="font-size:11.5px">{{ $message }}</div>
                    @enderror

                </div>

            </div>{{-- end section --}}

            {{-- ===== FOOTER NOTE ===== --}}
            <div class="lf-footer-note">
                <i class="bi bi-info-circle me-1"></i>
                Unless in emergency situations, employees are required to submit leave applications not less than
                <strong>five working days</strong> prior to the proposed date of commencement of leave.
            </div>

            {{-- ===== SUBMIT ===== --}}
            <div class="lf-submit-bar">
                <button type="submit" class="btn btn-primary btn-sm px-4" id="submitBtn">
                    <i class="bi bi-send me-1"></i> Submit Leave Request
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-sm" onclick="resetForm()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
            </div>

        </form>

    </div>{{-- end lf-paper --}}

<script>
function isSunday(date) {
    return date.getDay() === 0;
}

// Count working leave days (skip Sundays)
function countLeaveDays(startDate, endDate) {
    let count = 0;
    let current = new Date(startDate);

    while (current <= endDate) {
        if (!isSunday(current)) {
            count++;
        }
        current.setDate(current.getDate() + 1);
    }

    return count;
}

// Get return date (skip Sunday, handle Saturday rule)
function getReturnDate(endDate) {
    let retDate = new Date(endDate);
    retDate.setDate(retDate.getDate() + 1);

    // Skip Sunday
    if (retDate.getDay() === 0) {
        retDate.setDate(retDate.getDate() + 1);
    }

    return retDate;
}

// MAIN CALCULATION
function calcDays() {
    var from = document.getElementById('dateFrom').value;
    var to   = document.getElementById('dateTo').value;

    var ret  = document.getElementById('returnDate');
    var wrap = document.getElementById('daysSummaryWrap');
    var sum  = document.getElementById('daysSummary');

    if (!from || !to) {
        wrap.style.display = 'none';
        return;
    }

    var d1 = new Date(from);
    var d2 = new Date(to);

    if (d2 < d1) {
        wrap.style.display = 'none';
        return;
    }

    // ✅ WORKING DAYS ONLY (skip Sundays)
    var days = countLeaveDays(d1, d2);

    sum.textContent = days + (days === 1 ? ' day' : ' days');
    wrap.style.display = 'block';

    // Return date logic
    if (!ret.value) {
        let retDate = getReturnDate(d2);

        // If leave ends Saturday → Monday
        if (d2.getDay() === 6) {
            retDate = new Date(d2);
            retDate.setDate(retDate.getDate() + 2);
        }

        ret.value = retDate.toISOString().split('T')[0];
    }
}

// EVENTS
document.getElementById('dateFrom').addEventListener('change', calcDays);
document.getElementById('dateTo').addEventListener('change', calcDays);
calcDays();

// File name display
function showFileName(input) {
    var display = document.getElementById('fileNameDisplay');
    var nameSpan = document.getElementById('fileNameText');

    if (input.files && input.files[0]) {
        nameSpan.textContent = input.files[0].name;
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
}

// Reset helper
function resetForm() {
    document.getElementById('otherWrap').style.display = 'none';
    document.getElementById('daysSummaryWrap').style.display = 'none';
    document.getElementById('fileNameDisplay').style.display = 'none';
}

// Prevent double submit
document.getElementById('leaveForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
});
</script>

</body>

</html>