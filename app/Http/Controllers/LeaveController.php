<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Admin / HR side of the leave module.
 *
 * Workflow:  submitted → supervisor review → HR final decision → balance updated
 * All stage changes go through App\Services\LeaveWorkflow, which also sends
 * the emails to the employee, the supervisor and HR.
 */
class LeaveController extends Controller
{
    public function __construct(private LeaveWorkflow $workflow, private LeaveDayCalculator $days) {}

    /**
     * Public Leave Application Form
     */
    public function create()
    {
        return view('dashboard.leave.apply');
    }

    /**
     * Store Leave Request (public form)
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_name'          => 'required|string|max:255',
            'employee_number'        => 'nullable|string|max:255',
            'position'               => 'nullable|string|max:255',
            'email'                  => 'required|email',

            'leave_type'             => 'required|string',
            'other_leave_type'       => 'nullable|string|max:255',

            'date_from'              => 'required|date',
            'date_to'                => 'required|date|after_or_equal:date_from',
            'return_date'            => 'nullable|date',

            'reason'                 => 'nullable|string',
            'late_application_reason'=> 'nullable|string',

            'supporting_document'    => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        $from = Carbon::parse($request->date_from);
        $to   = Carbon::parse($request->date_to);

        // Company day rules: Sundays and Zambian public holidays aren't counted.
        $calc = $this->days->calculate($from, $to);

        $documentPath = $request->hasFile('supporting_document')
            ? $request->file('supporting_document')->store('leave-documents', 'public')
            : null;

        // Link to the employee record when we can, so the supervisor and
        // the leave balance work for public-form submissions too.
        $employee = $this->matchEmployee($request->employee_number, $request->email);

        $leave = LeaveRequest::create([
            'employee_id'             => $employee?->id,
            'employee_name'           => $request->employee_name,
            'employee_number'         => $request->employee_number ?: $employee?->employee_id,
            'position'                => $request->position ?: $employee?->position,
            'email'                   => $request->email,

            'leave_type'              => $request->leave_type,
            'other_leave_type'        => $request->other_leave_type,

            'date_from'               => $from,
            'date_to'                 => $to,
            'return_date'             => $request->return_date ?: $calc['return_date'],
            'total_days'              => max(1, $calc['working_days']),
            'calendar_days'           => $calc['calendar_days'],
            'exclude_sundays'         => $calc['options']['exclude_sundays'],
            'exclude_saturdays'       => $calc['options']['exclude_saturdays'],
            'exclude_holidays'        => $calc['options']['exclude_holidays'],
            'excluded_dates'          => $calc['excluded'],

            'reason'                  => $request->reason,
            'late_application_reason' => $request->late_application_reason,

            'applicant_signature_date'=> now(),
            'status'                  => 'Pending',
            'supporting_document'     => $documentPath,
        ]);

        $this->workflow->submit($leave);

        return redirect()
            ->back()
            ->with('success', 'Leave request submitted. A confirmation email is on its way.');
    }

    /**
     * Leave list with stage filter.
     */
    public function index(Request $request)
    {
        $stage = $request->query('stage');

        $applications = LeaveRequest::with(['employee', 'supervisorEmployee', 'supervisorUser'])
            ->stage($stage)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->where(fn ($w) => $w->where('employee_name', 'like', $term)
                    ->orWhere('employee_number', 'like', $term));
            })
            ->when($request->boolean('mine'), fn ($q) => $q->where('supervisor_user_id', $request->user()->id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all'                           => LeaveRequest::count(),
            LeaveRequest::STAGE_SUPERVISOR  => LeaveRequest::stage(LeaveRequest::STAGE_SUPERVISOR)->count(),
            LeaveRequest::STAGE_HR          => LeaveRequest::stage(LeaveRequest::STAGE_HR)->count(),
            LeaveRequest::STAGE_APPROVED    => LeaveRequest::stage(LeaveRequest::STAGE_APPROVED)->count(),
            LeaveRequest::STAGE_REJECTED    => LeaveRequest::stage(LeaveRequest::STAGE_REJECTED)->count(),
        ];

        return view('dashboard.leave.index', compact('applications', 'counts', 'stage'));
    }

    /**
     * View Leave Request
     */
    public function show(LeaveRequest $leave)
    {
        $leave->load(['employee.supervisorEmployee', 'employee.supervisorUser', 'supervisorEmployee.user', 'supervisorUser']);

        $employee = $leave->employee;
        $balance  = $employee ? (float) $employee->leave_balance : null;
        $after    = ($employee && $leave->isDeductible()) ? $balance - (float) $leave->total_days : $balance;

        return view('dashboard.leave.show', [
            'leave'        => $leave,
            'employee'     => $employee,
            'balance'      => $balance,
            'balanceAfter' => $after,
            'insufficient' => $employee && $leave->isDeductible() && $after < 0,
        ]);
    }

    /**
     * Stage 2 — supervisor recommendation (assigned admin supervisor, or any
     * admin acting on the supervisor's behalf).
     */
    public function updateSupervisor(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'supervisor_decision' => 'required|in:Approved,Rejected',
            'supervisor_comments' => 'nullable|required_if:supervisor_decision,Rejected|string|max:5000',
        ], [
            'supervisor_comments.required_if' => 'Please give a reason when recommending rejection.',
        ]);

        try {
            $this->workflow->supervisorDecision($leave, $request->user(), $data['supervisor_decision'], $data['supervisor_comments'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Supervisor decision recorded. HR and the employee have been notified.');
    }

    /**
     * Stage 3 — FINAL approval (HR / Admin). Deducts the leave balance.
     */
    public function approve(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'hr_comments'      => 'nullable|string|max:5000',
            'override_balance' => 'nullable|boolean',
        ]);

        try {
            $this->workflow->approve($leave, $request->user(), $data['hr_comments'] ?? null, $request->boolean('override_balance'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Leave approved. The balance has been updated and everyone has been notified.');
    }

    /**
     * Stage 3 — FINAL rejection (HR / Admin).
     */
    public function reject(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'hr_comments' => 'required|string|max:5000',
        ], [
            'hr_comments.required' => 'Please give a reason for the rejection.',
        ]);

        try {
            $this->workflow->reject($leave, $request->user(), $data['hr_comments']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Leave rejected. The employee and supervisor have been notified.');
    }

    /**
     * Delete Leave Request (restores any deducted balance).
     */
    public function destroy(LeaveRequest $leave)
    {
        $this->workflow->restoreBalance($leave);

        if ($leave->supporting_document) {
            Storage::disk('public')->delete($leave->supporting_document);
        }

        $leave->delete();

        return redirect()
            ->route('leave.index')
            ->with('success', 'Leave request deleted.');
    }

    /**
     * Kept for the printable form. Only updates the paper "HR record" figures;
     * approval and balance changes happen through approve().
     */
    public function updateHr(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'days_accrued'    => 'nullable|numeric',
            'days_available'  => 'nullable|numeric',
            'days_requested'  => 'nullable|numeric',
            'days_balance'    => 'nullable|numeric',
            'hr_name'         => 'nullable|string',
            'hr_position'     => 'nullable|string',
        ]);

        $leave->update($request->only([
            'days_accrued', 'days_available', 'days_requested', 'days_balance', 'hr_name', 'hr_position',
        ]));

        return back()->with('success', 'HR record updated successfully.');
    }

    public function print($id)
    {
        $leave = LeaveRequest::findOrFail($id);

        return view('dashboard.leave.print', compact('leave'));
    }

    private function matchEmployee(?string $number, ?string $email): ?Employee
    {
        if ($number && ($e = Employee::where('employee_id', trim($number))->first())) {
            return $e;
        }

        if ($email) {
            return Employee::where('company_email', $email)
                ->orWhere('personal_email', $email)
                ->orWhereHas('user', fn ($q) => $q->where('email', $email))
                ->first();
        }

        return null;
    }
}
