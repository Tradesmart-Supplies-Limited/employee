<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public const TYPES = [
        'Annual', 'Sick', 'Maternity', 'Paternal',
        'Compassionate', 'Unpaid', 'Study', 'Other',
    ];

    public function __construct(private LeaveWorkflow $workflow, private LeaveDayCalculator $days) {}

    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        $leaves = $employee->leaveRequests()
            ->with(['supervisorEmployee', 'supervisorUser'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'balance'       => (float) $employee->leave_balance,
            'entitlement'   => (float) $employee->annual_leave_entitlement,
            'pending_days'  => $employee->leavePendingDays(),
            'approved_days' => $employee->leaveRequests()
                ->where('status', 'Approved')
                ->whereYear('date_from', now()->year)
                ->sum('total_days'),
            'pending' => $employee->leaveRequests()->where('status', 'Pending')->count(),
            'rejected' => $employee->leaveRequests()
                ->where('status', 'Rejected')
                ->whereYear('date_from', now()->year)
                ->count(),
        ];

        return view('portal.leave.index', compact('leaves', 'summary', 'employee'));
    }

    public function create(Request $request)
    {
        $employee = $request->user()->employee->load(['supervisorEmployee', 'supervisorUser']);
        $types    = self::TYPES;

        return view('portal.leave.create', [
            'employee'        => $employee,
            'types'           => $types,
            'deductibleTypes' => config('leave.deductible_types', ['Annual']),
            'pendingDays'     => $employee->leavePendingDays(),
            'allowNegative'   => (bool) config('leave.allow_negative_balance'),
            // For the live day counter: holidays this year → next year
            'holidays'        => $this->days->holidays(now()->year - 1, now()->year + 1),
            'dayRules'        => LeaveDayCalculator::defaultOptions(),
            'canChangeRules'  => (bool) config('leave.employee_can_change_day_rules', true),
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;

        $isLate = $request->filled('date_from')
            && Carbon::parse($request->date_from)->lt(today());

        $data = $request->validate([
            'leave_type'              => ['required', Rule::in(self::TYPES)],
            'other_leave_type'        => 'nullable|required_if:leave_type,Other|string|max:255',
            'date_from'               => 'required|date',
            'date_to'                 => 'required|date|after_or_equal:date_from',
            'reason'                  => 'nullable|string|max:5000',
            'late_application_reason' => [Rule::requiredIf($isLate), 'nullable', 'string', 'max:5000'],
            'supporting_document'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        $from = Carbon::parse($data['date_from']);
        $to   = Carbon::parse($data['date_to']);

        // Block overlapping pending / approved requests.
        $overlap = $employee->leaveRequests()
            ->whereIn('status', ['Pending', 'Approved'])
            ->whereDate('date_from', '<=', $to)
            ->whereDate('date_to', '>=', $from)
            ->exists();

        if ($overlap) {
            return back()->withInput()->withErrors([
                'date_from' => 'You already have a pending or approved leave request overlapping these dates.',
            ]);
        }

        // Count chargeable days: skips Sundays / public holidays per the day rules.
        $calc = $this->days->calculate($from, $to, $this->dayRules($request));
        $days = $calc['working_days'];

        if ($days < 1) {
            return back()->withInput()->withErrors([
                'date_to' => 'Every day you selected is a Sunday or public holiday, so there are no leave days to take.',
            ]);
        }

        // Balance check for leave types that reduce the balance.
        if (in_array($data['leave_type'], config('leave.deductible_types', ['Annual']), true)
            && ! config('leave.allow_negative_balance')) {
            $available = (float) $employee->leave_balance - $employee->leavePendingDays();

            if ($days > $available) {
                return back()->withInput()->withErrors([
                    'date_to' => sprintf(
                        'You are requesting %d day(s) but only have %s day(s) available%s.',
                        $days,
                        rtrim(rtrim(number_format(max($available, 0), 1), '0'), '.'),
                        $employee->leavePendingDays() > 0 ? ' after your other pending requests' : ''
                    ),
                ]);
            }
        }

        $documentPath = $request->hasFile('supporting_document')
            ? $request->file('supporting_document')->store('leave-documents', 'public')
            : null;

        $leave = LeaveRequest::create([
            'employee_id'             => $employee->id,
            'employee_name'           => $employee->full_name,
            'employee_number'         => $employee->employee_id,
            'position'                => $employee->position,
            'email'                   => $request->user()->email,

            'leave_type'              => $data['leave_type'],
            'other_leave_type'        => $data['other_leave_type'] ?? null,

            'date_from'               => $from,
            'date_to'                 => $to,
            'return_date'             => $calc['return_date'],
            'total_days'              => $days,
            'calendar_days'           => $calc['calendar_days'],
            'exclude_sundays'         => $calc['options']['exclude_sundays'],
            'exclude_saturdays'       => $calc['options']['exclude_saturdays'],
            'exclude_holidays'        => $calc['options']['exclude_holidays'],
            'excluded_dates'          => $calc['excluded'],

            'reason'                  => $data['reason'] ?? null,
            'late_application_reason' => $data['late_application_reason'] ?? null,

            'applicant_signature_date' => now(),
            'status'                  => 'Pending',
            'supporting_document'     => $documentPath,
        ]);

        // Assigns the supervisor and emails the employee, supervisor and HR.
        $this->workflow->submit($leave);

        return redirect()
            ->route('portal.leave.show', $leave)
            ->with('success', 'Leave request submitted. You will be emailed when it is reviewed.');
    }

    public function show(Request $request, LeaveRequest $leave)
    {
        $this->authorizeOwner($request, $leave);

        $leave->load(['supervisorEmployee', 'supervisorUser', 'employee']);

        return view('portal.leave.show', compact('leave'));
    }

    /** Employees can withdraw a request only while it is still pending. */
    public function destroy(Request $request, LeaveRequest $leave)
    {
        $this->authorizeOwner($request, $leave);

        if ($leave->isFinal()) {
            return back()->with('error', 'Only requests still awaiting a final decision can be withdrawn.');
        }

        if ($leave->supporting_document) {
            Storage::disk('public')->delete($leave->supporting_document);
        }

        $leave->delete();

        return redirect()
            ->route('portal.leave.index')
            ->with('success', 'Leave request withdrawn.');
    }

    /** Day rules from the form, or the company defaults if employees may not change them. */
    private function dayRules(Request $request): array
    {
        if (! config('leave.employee_can_change_day_rules', true)) {
            return LeaveDayCalculator::defaultOptions();
        }

        return [
            'exclude_sundays'   => $request->boolean('exclude_sundays'),
            'exclude_saturdays' => $request->boolean('exclude_saturdays'),
            'exclude_holidays'  => $request->boolean('exclude_holidays'),
        ];
    }

    private function authorizeOwner(Request $request, LeaveRequest $leave): void
    {
        abort_unless($leave->employee_id === $request->user()->employee_id, 404);
    }
}
