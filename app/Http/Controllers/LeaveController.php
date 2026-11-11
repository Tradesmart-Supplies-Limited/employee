<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\LeaveDayCalculator;
use App\Services\LeaveWorkflow;
use App\Support\DeptScope;
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
     * Apply for leave.
     *   Staff (managers, supervisors, HR) signed in with an employee record:
     *   their own request, in the staff area, details filled in.
     *   Everyone else: the public form.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $portal = app(\App\Http\Controllers\Portal\LeaveController::class);

        if ($user && $user->canUseAdmin()) {
            $employee = $user->employee?->load(['supervisorEmployee', 'supervisorUser']);

            return view('dashboard.leave.apply-staff', ($employee ? $portal->formData($employee) : ['employee' => null]) + [
                'action'    => route('leave.store'),
                'cancelUrl' => route('leave.index'),
                'origin'    => 'staff',
            ]);
        }

        return view('dashboard.leave.apply', [
            'action'          => route('leave.store'),
            'employee'        => null,
            'types'           => \App\Http\Controllers\Portal\LeaveController::TYPES,
            'deductibleTypes' => config('leave.deductible_types', ['Annual']),
            'pendingDays'     => 0,
            'allowNegative'   => true,
            'holidays'        => $this->days->holidays(now()->year - 1, now()->year + 1),
            'dayRules'        => LeaveDayCalculator::defaultOptions(),
            'canChangeRules'  => false,
            'approvalRoute'   => $this->workflow->previewRoute(null),
            'cancelUrl'       => null,
            'origin'          => null,
        ]);
    }

    /**
     * Store Leave Request (public form)
     */
    public function store(Request $request)
    {
        // Signed-in staff with an employee record apply for themselves:
        // same rules as the portal (balance check, overlap check, day rules).
        if ($request->user()?->employee && $request->user()->canUseAdmin()) {
            $request->merge(['_origin' => 'staff']);

            return app(\App\Http\Controllers\Portal\LeaveController::class)->store($request);
        }

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

            'reason'                 => 'nullable|required_if:leave_type,Compassionate,Unpaid,Other,Study|string',
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
            ->with('success', 'Thank you, your leave request has been submitted. A confirmation email is on its way.');
    }

    /**
     * Leave list with stage filter.
     */
    public function index(Request $request)
    {
        $stage = $request->query('stage');

        // Managers: only their department's requests.
        $base = fn () => DeptScope::viaEmployee(LeaveRequest::query(), $request->user());

        $applications = $base()->with(['employee', 'supervisorEmployee', 'supervisorUser'])
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
            'all'                           => $base()->count(),
            LeaveRequest::STAGE_SUPERVISOR  => $base()->stage(LeaveRequest::STAGE_SUPERVISOR)->count(),
            LeaveRequest::STAGE_HR          => $base()->stage(LeaveRequest::STAGE_HR)->count(),
            LeaveRequest::STAGE_DIRECTOR    => $base()->stage(LeaveRequest::STAGE_DIRECTOR)->count(),
            LeaveRequest::STAGE_APPROVED    => $base()->stage(LeaveRequest::STAGE_APPROVED)->count(),
            LeaveRequest::STAGE_REJECTED    => $base()->stage(LeaveRequest::STAGE_REJECTED)->count(),
        ];

        return view('dashboard.leave.index', compact('applications', 'counts', 'stage'));
    }

    /**
     * View Leave Request
     */
    public function show(LeaveRequest $leave)
    {
        $this->authorizeLeave($leave);
        $leave->load(['employee.supervisorEmployee', 'employee.supervisorUser', 'supervisorEmployee.user', 'supervisorUser']);

        $employee = $leave->employee;
        $balance  = $employee ? (float) $employee->leave_balance : null;
        $after    = ($employee && $leave->isDeductible()) ? $balance - (float) $leave->total_days : $balance;

        return view('dashboard.leave.show', [
            'actingStep'   => $this->workflow->actingStep($leave, request()->user()),
            'overrideStep' => $this->workflow->overrideStep($leave, request()->user()),
            'canComment'   => $this->workflow->canComment($leave, request()->user()),
            'comments'     => $leave->comments()->get(),
            'leave'        => $leave,
            'employee'     => $employee,
            'balance'      => $balance,
            'balanceAfter' => $after,
            'insufficient' => $employee && $leave->isDeductible() && $after < 0,
        ]);
    }

    /**
     * One approval step: the supervisor, HR or the Director approves or
     * reverts. Approval at the last step is final and deducts the balance;
     * a revert at any step ends the request (a higher approver may override it). Who may decide which step
     * is checked in LeaveWorkflow::canAct().
     */
    public function decide(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'step'             => 'required|in:supervisor,hr,director',
            'decision'         => 'required|in:Approved,Rejected',
            'comments'         => 'nullable|required_if:decision,Rejected|string|max:5000',
            'override_balance' => 'nullable|boolean',
        ], [
            'comments.required_if' => 'Kindly add a short reason, so the applicant understands why it was reverted.',
        ]);

        $this->authorizeLeave($leave);

        return $this->record($request, $leave, $data['step'], $data['decision'], $data['comments'] ?? null);
    }

    /** Older form: the supervisor step. */
    public function updateSupervisor(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'supervisor_decision' => 'required|in:Approved,Rejected',
            'supervisor_comments' => 'nullable|required_if:supervisor_decision,Rejected|string|max:5000',
        ], [
            'supervisor_comments.required_if' => 'Kindly add a short reason, so the applicant understands why it was reverted.',
        ]);

        $this->authorizeLeave($leave);

        return $this->record($request, $leave, 'supervisor', $data['supervisor_decision'], $data['supervisor_comments'] ?? null);
    }

    /** Approve at whichever step is yours (HR, Director, or on the supervisor's behalf). */
    public function approve(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'hr_comments'      => 'nullable|string|max:5000',
            'override_balance' => 'nullable|boolean',
        ]);

        $step = $this->workflow->actingStep($leave, $request->user());
        if (! $step) {
            return back()->with('error', $leave->isFinal() ? 'A final decision has already been made on this request.' : 'It is not your turn to approve this request.');
        }

        return $this->record($request, $leave, $step, 'Approved', $data['hr_comments'] ?? null);
    }

    /** Revert at whichever step is yours. */
    public function reject(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'hr_comments' => 'required|string|max:5000',
        ], [
            'hr_comments.required' => 'Kindly add a short reason, so the applicant understands why it was reverted.',
        ]);

        $step = $this->workflow->actingStep($leave, $request->user());
        if (! $step) {
            return back()->with('error', $leave->isFinal() ? 'A final decision has already been made on this request.' : 'It is not your turn to decide this request.');
        }

        return $this->record($request, $leave, $step, 'Rejected', $data['hr_comments']);
    }

    private function record(Request $request, LeaveRequest $leave, string $step, string $decision, ?string $comments)
    {
        try {
            $leave = $this->workflow->decide($leave, $request->user(), $step, $decision, $comments, $request->boolean('override_balance'));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $label = LeaveRequest::STEPS[$step]['title'];

        return back()->with('success', match (true) {
            $leave->status === 'Rejected' => "Thank you. The request has been reverted at the {$label} step, and the applicant and everyone in the approval chain have been informed.",
            $leave->status === 'Approved' => 'Thank you. The leave is now approved, the balance has been updated, and everyone has been informed.',
            default => "Thank you, your {$label} is recorded. It has moved on to " . LeaveRequest::STEPS[$leave->currentStep()]['who'] . ', who has been emailed.',
        });
    }

    /** Add a comment (anyone in the chain, or the applicant). */
    public function comment(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate(['body' => 'required|string|max:3000'], ['body.required' => 'Please write a comment before sending.']);
        $this->authorizeLeave($leave);

        try {
            $this->workflow->comment($leave, $request->user(), $data['body']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(route('leave.show', $leave) . '#comments')->with('success', 'Thank you, your comment has been added and everyone involved has been informed.');
    }

    /** A higher approver overrides a revert made at an earlier step. */
    public function override(Request $request, LeaveRequest $leave)
    {
        $data = $request->validate([
            'override_reason'  => 'required|string|max:5000',
            'override_balance' => 'nullable|boolean',
        ], ['override_reason.required' => 'Please add a short note explaining the override, so everyone understands the change.']);
        $this->authorizeLeave($leave);

        try {
            $leave = $this->workflow->override($leave, $request->user(), $data['override_reason'], $request->boolean('override_balance'));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', $leave->status === 'Approved'
            ? 'Thank you. The revert has been overridden and the leave is now approved. Everyone involved has been informed.'
            : 'Thank you. The revert has been overridden and the request has moved on to ' . LeaveRequest::STEPS[$leave->currentStep()]['who'] . '.');
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
            ->with('success', 'The leave request has been deleted.');
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

        return back()->with('success', 'Thank you, the HR record has been updated.');
    }

    public function print($id)
    {
        $leave = LeaveRequest::findOrFail($id);
        $this->authorizeLeave($leave);

        return view('dashboard.leave.print', compact('leave'));
    }

    /** Managers may only open their own department's requests. */
    private function authorizeLeave(LeaveRequest $leave): void
    {
        $user = request()->user();
        abort_unless($user && ($user->hasFullAccess() || $user->canSeeEmployee($leave->employee)), 403, 'This request isn\'t from your department.');
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
