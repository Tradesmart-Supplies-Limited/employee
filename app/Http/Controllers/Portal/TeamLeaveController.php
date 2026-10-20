<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveWorkflow;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Portal: leave requests from the people who report to the logged-in employee.
 * The supervisor is the first step of the approval chain
 * (Supervisor → HR → Director). Rejecting here ends the request.
 */
class TeamLeaveController extends Controller
{
    public function __construct(private LeaveWorkflow $workflow) {}

    public function index(Request $request)
    {
        $me = $request->user()->employee;

        $awaiting = LeaveRequest::with('employee')
            ->where('supervisor_employee_id', $me->id)
            ->stage(LeaveRequest::STAGE_SUPERVISOR)
            ->oldest()
            ->get();

        $history = LeaveRequest::with('employee')
            ->where('supervisor_employee_id', $me->id)
            ->where(fn ($q) => $q->whereNotNull('supervisor_decision')->orWhere('status', '!=', 'Pending'))
            ->latest()
            ->paginate(10);

        $team = $me->directReports()->orderBy('first_name')->get();

        return view('portal.team-leave.index', compact('awaiting', 'history', 'team'));
    }

    public function show(Request $request, LeaveRequest $leave)
    {
        $this->authorizeSupervisor($request, $leave);

        $leave->load(['employee', 'supervisorEmployee', 'supervisorUser']);

        return view('portal.team-leave.show', compact('leave'));
    }

    public function decide(Request $request, LeaveRequest $leave)
    {
        $this->authorizeSupervisor($request, $leave);

        $data = $request->validate([
            'supervisor_decision' => 'required|in:Approved,Rejected',
            'supervisor_comments' => 'nullable|required_if:supervisor_decision,Rejected|string|max:5000',
        ], [
            'supervisor_comments.required_if' => 'Please give a reason when rejecting.',
        ]);

        try {
            $leave = $this->workflow->supervisorDecision($leave, $request->user(), $data['supervisor_decision'], $data['supervisor_comments'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('portal.team-leave.index')
            ->with('success', match ($leave->status) {
                'Rejected' => 'You rejected the request. The employee and HR have been notified.',
                'Approved' => 'Approved. As the last step in the chain, your approval is final and the balance has been updated.',
                default    => 'Approved. It has moved on to ' . LeaveRequest::STEPS[$leave->currentStep()]['who'] . ' for the next approval.',
            });
    }

    private function authorizeSupervisor(Request $request, LeaveRequest $leave): void
    {
        abort_unless(
            $leave->supervisor_employee_id && $leave->supervisor_employee_id === $request->user()->employee_id,
            404
        );
    }
}
