@extends('layouts.app')

@section('content')

{{-- ===== BACK BAR ===== --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Leave Application <small class="text-muted fs-6">#LV-{{ str_pad($leave->id, 5, '0', STR_PAD_LEFT) }}</small></h4>
        <small class="text-muted">
            Submitted {{ $leave->created_at->format('D, M d Y') }}
            &nbsp;·&nbsp;
            <span class="badge bg-{{ $leave->stage_color }} {{ in_array($leave->stage_color, ['warning','info']) ? 'text-dark' : '' }}">
                <i class="bi {{ $leave->stage_icon }} me-1"></i>{{ $leave->stage_label }}
            </span>
        </small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.open('{{ route('leave.print', $leave->id) }}','_blank')"
            class="btn btn-sm btn-outline-secondary" title="Print">
            <i class="bi bi-printer"></i>
        </button>
        <a href="{{ route('leave.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- ===== STAGE TRACKER ===== --}}
<div class="lv-paper mb-3">
    <div class="p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="lv-field-label mb-0">Application progress</div>
            <div class="small text-muted">
                Supervisor:
                <strong>{{ $leave->assigned_supervisor_name ?? 'Not assigned' }}</strong>
                @if(! $leave->hasAssignedSupervisor() && $employee && auth()->user()?->hasFullAccess())
                    <a href="{{ route('employees.edit', $employee) }}" class="ms-1 small">Assign one</a>
                @endif
            </div>
        </div>
        @include('leave.partials.tracker', ['leave' => $leave])
    </div>
</div>

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
        <div class="lv-field mt-3">
            <div class="lv-field-label">Leave applied for</div>
            <div class="lv-leave-grid mt-2">
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
                @include('leave.partials.day-breakdown', ['leave' => $leave])
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
         SECTION 2 — SUPERVISOR RECOMMENDATION
    ====================================================== --}}
    <div class="lv-section lv-section-shaded">
        <div class="lv-section-heading">
            Step 2 · Supervisor review
            <span class="lv-heading-note">(recommendation — final decision is made by HR)</span>
        </div>

        @if(filled($leave->supervisor_decision))
            @php $rec = $leave->supervisor_decision === 'Approved'; @endphp
            <div class="lv-decision {{ $rec ? 'lv-decision--ok' : 'lv-decision--no' }}">
                <i class="bi {{ $rec ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-down-fill' }}"></i>
                <div>
                    <div class="fw-semibold">{{ $rec ? 'Recommended for approval' : 'Not recommended' }}</div>
                    <div class="small text-muted">
                        {{ $leave->supervisor_name }}@if($leave->supervisor_position) · {{ $leave->supervisor_position }}@endif
                        · {{ optional($leave->supervisor_acted_at ?? $leave->supervisor_signature_date)->format('d M Y, H:i') }}
                    </div>
                    @if($leave->supervisor_comments)
                        <div class="mt-2 small">{!! nl2br(e($leave->supervisor_comments)) !!}</div>
                    @endif
                </div>
            </div>
        @elseif($leave->isFinal())
            <div class="text-muted small"><i class="bi bi-skip-forward me-1"></i>Skipped. HR made the final decision directly.</div>
        @else
            @php $isMine = $leave->isAssignedSupervisor(auth()->user()); @endphp
            @if(! $isMine)
                <div class="alert alert-light border small py-2 mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    @if($leave->hasAssignedSupervisor())
                        Waiting for <strong>{{ $leave->assigned_supervisor_name }}</strong>. You can record the decision on their behalf.
                    @else
                        No supervisor is assigned. You can record a supervisor decision, or go straight to the final decision below.
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('leave.supervisor.update', $leave->id) }}" id="supervisorForm">
                @csrf
                @method('PATCH')

                <div class="lv-approval-row">
                    <label class="lv-choice lv-choice--ok">
                        <input type="radio" name="supervisor_decision" value="Approved" @checked(old('supervisor_decision') === 'Approved') required>
                        <span><i class="bi bi-hand-thumbs-up"></i> Recommend approval</span>
                    </label>
                    <label class="lv-choice lv-choice--no">
                        <input type="radio" name="supervisor_decision" value="Rejected" @checked(old('supervisor_decision') === 'Rejected')>
                        <span><i class="bi bi-hand-thumbs-down"></i> Recommend rejection</span>
                    </label>
                </div>

                <div class="lv-field mt-3">
                    <label class="lv-field-label" for="supervisor_comments">Comments <span class="lv-hint">(required when recommending rejection)</span></label>
                    <textarea name="supervisor_comments" id="supervisor_comments" rows="3" class="lv-textarea">{{ old('supervisor_comments') }}</textarea>
                </div>

                <div class="mt-3 d-flex align-items-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-send-check me-1"></i> Submit supervisor decision
                    </button>
                    <small class="text-muted">Signed as <strong>{{ auth()->user()->name }}</strong>. Emails the employee, supervisor and HR.</small>
                </div>
            </form>
        @endif
    </div>


    {{-- =====================================================
         SECTION 3 — HR FINAL DECISION + LEAVE BALANCE
    ====================================================== --}}
    @php $n = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.'); @endphp
    <div class="lv-section lv-section-shaded">
        <div class="lv-section-heading">
            Step 3 · HR final decision
            <span class="lv-heading-note">(Annual leave entitlement {{ $n($employee->annual_leave_entitlement ?? config('leave.annual_entitlement')) }} days)</span>
        </div>

        {{-- Balance strip --}}
        @if($employee)
            <div class="lv-balance">
                @if($leave->status === 'Approved')
                    <div><span>Balance before</span><strong>{{ $n($leave->days_available) }}</strong></div>
                    <div><span>Deducted</span><strong class="text-danger">−{{ $n($leave->balance_deducted) }}</strong></div>
                    <div><span>Balance after</span><strong class="text-success">{{ $n($leave->days_balance) }}</strong></div>
                    <div><span>Current balance</span><strong>{{ $n($employee->leave_balance) }}</strong></div>
                @else
                    <div><span>Current balance</span><strong>{{ $n($balance) }}</strong></div>
                    <div><span>This request</span><strong>{{ $leave->isDeductible() ? '−' . $n($leave->total_days) : '0' }}</strong></div>
                    <div><span>Balance after approval</span><strong class="{{ $insufficient ? 'text-danger' : 'text-success' }}">{{ $n($balanceAfter) }}</strong></div>
                    <div><span>Taken this year</span><strong>{{ $n($employee->leaveTakenInYear()) }}</strong></div>
                @endif
            </div>
            @if(! $leave->isDeductible())
                <div class="small text-muted mt-2"><i class="bi bi-info-circle me-1"></i>{{ $leave->leave_type }} leave is recorded but doesn't reduce the annual balance.</div>
            @endif
        @else
            <div class="alert alert-warning small py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                This request isn't linked to an employee record (submitted via the public form with an unknown employee number / email), so no balance will be updated.
            </div>
        @endif

        @if($leave->isFinal())
            @php $ok = $leave->status === 'Approved'; @endphp
            <div class="lv-decision mt-3 {{ $ok ? 'lv-decision--ok' : 'lv-decision--no' }}">
                <i class="bi {{ $ok ? 'bi-patch-check-fill' : 'bi-x-octagon-fill' }}"></i>
                <div>
                    <div class="fw-semibold">{{ $ok ? 'Approved' : 'Rejected' }}</div>
                    <div class="small text-muted">
                        {{ $leave->hr_name }}@if($leave->hr_position) · {{ $leave->hr_position }}@endif
                        · {{ optional($leave->hr_acted_at ?? $leave->hr_signature_date)->format('d M Y, H:i') }}
                    </div>
                    @if($leave->hr_comments)
                        <div class="mt-2 small">{!! nl2br(e($leave->hr_comments)) !!}</div>
                    @endif
                </div>
            </div>
        @elseif(! auth()->user()?->hasFullAccess())
            <div class="alert alert-light border small py-2 mt-3 mb-0">
                <i class="bi bi-hourglass-split me-1"></i>
                Waiting for HR's final decision. {{ filled($leave->supervisor_decision) ? 'Your recommendation has been sent to HR.' : 'Record your recommendation above first.' }}
            </div>
        @else
            @if($leave->awaitingSupervisor())
                <div class="alert alert-light border small py-2 mt-3 mb-0">
                    <i class="bi bi-hourglass-split me-1"></i>
                    The supervisor hasn't reviewed this yet. You can still make the final decision now; the supervisor step will be marked as skipped.
                </div>
            @endif

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('leave.approve', $leave->id) }}" class="lv-action lv-action--ok"
                          onsubmit="return confirm('Give final approval{{ $employee && $leave->isDeductible() ? ' and deduct ' . $n($leave->total_days) . ' day(s) from the balance' : '' }}?');">
                        @csrf
                        <div class="fw-semibold mb-2"><i class="bi bi-check-circle me-1"></i> Final approval</div>
                        <textarea name="hr_comments" rows="2" class="lv-textarea" placeholder="Optional note to the employee"></textarea>
                        @if($insufficient)
                            <label class="d-flex gap-2 align-items-start small text-danger mt-2">
                                <input type="checkbox" name="override_balance" value="1" class="mt-1">
                                <span>Approve anyway: balance will go to {{ $n($balanceAfter) }} days</span>
                            </label>
                        @endif
                        <button class="btn btn-success btn-sm w-100 mt-2"><i class="bi bi-check2-all me-1"></i> Approve leave</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form method="POST" action="{{ route('leave.reject', $leave->id) }}" class="lv-action lv-action--no"
                          onsubmit="return confirm('Reject this leave request?');">
                        @csrf
                        <div class="fw-semibold mb-2"><i class="bi bi-x-circle me-1"></i> Final rejection</div>
                        <textarea name="hr_comments" rows="2" class="lv-textarea" placeholder="Reason (required)" required></textarea>
                        <button class="btn btn-outline-danger btn-sm w-100 mt-2"><i class="bi bi-x-lg me-1"></i> Reject leave</button>
                    </form>
                </div>
            </div>
            <small class="text-muted d-block mt-2">Signed as <strong>{{ auth()->user()->name }}</strong>. The employee, supervisor and HR get an email.</small>
        @endif
    </div>


    {{-- FOOTER NOTE --}}
    <div class="lv-footer-note">
        <i class="bi bi-info-circle me-1"></i>
        Unless in emergency situations, employees are required to submit leave applications not less than
        <strong>five working days</strong> prior to the proposed date of commencement of leave.
    </div>

</div>{{-- end lv-paper --}}


{{-- ===== STYLES ===== --}}
<style>
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
    border-bottom: 4px solid var(--brand, #185FA5);
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
    color: var(--brand-text, #185FA5);
}

.lv-company-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--brand-text, #185FA5);
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
    gap: 3px;
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
    min-height: 26px;
}

.lv-field-value--block {
    border: 1px solid #e0e0e0;
    border-radius: 3px;
    padding: 8px 10px;
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
    padding: 8px 12px;
    border-bottom: 1px solid #e0e0e0;
    border-right: 1px solid #e0e0e0;
    font-size: 13px;
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
    font-size: 11px;
    color: #bbb;
    width: 16px;
    flex-shrink: 0;
}

.lv-leave-mark {
    font-size: 14px;
    font-weight: 700;
    color: var(--brand-text, #185FA5);
    width: 16px;
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
    color: var(--brand-text, #185FA5);
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
    min-height: 32px;
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
    border-bottom-color: var(--brand, #185FA5);
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
    border-color: var(--brand, #185FA5);
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
    accent-color: var(--brand, #185FA5);
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
    border-top: 3px solid var(--brand, #185FA5);
    background: #f2f8ff;
    font-style: italic;
}

/* ---- Workflow additions ---- */
.lv-decision { display: flex; gap: 12px; align-items: flex-start; padding: 12px 14px; border-radius: 8px; border: 1px solid #e5e7eb; background: #fff; }
.lv-decision > i { font-size: 1.4rem; line-height: 1; }
.lv-decision--ok { border-color: #bbf7d0; background: #f0fdf4; }
.lv-decision--ok > i { color: #16a34a; }
.lv-decision--no { border-color: #fecaca; background: #fef2f2; }
.lv-decision--no > i { color: #dc2626; }

.lv-choice { position: relative; cursor: pointer; margin: 0; }
.lv-choice input { position: absolute; opacity: 0; }
.lv-choice span { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600; color: #475569; background: #fff; transition: all .15s; }
.lv-choice--ok input:checked + span { border-color: #16a34a; background: #f0fdf4; color: #15803d; }
.lv-choice--no input:checked + span { border-color: #dc2626; background: #fef2f2; color: #b91c1c; }
.lv-choice input:focus-visible + span { outline: 2px solid var(--brand, #185FA5); outline-offset: 2px; }

.lv-balance { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: #fff; }
.lv-balance > div { padding: 10px 12px; border-right: 1px solid #eef2f7; }
.lv-balance > div:last-child { border-right: none; }
.lv-balance span { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #888; }
.lv-balance strong { font-size: 1.25rem; }

.lv-action { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; background: #fff; height: 100%; }
.lv-action--ok { border-top: 3px solid #16a34a; }
.lv-action--no { border-top: 3px solid #dc2626; }

@media (max-width: 640px) {
    .lv-balance { grid-template-columns: 1fr 1fr; }
    .lv-balance > div:nth-child(2) { border-right: none; }
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


@endsection