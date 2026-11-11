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
 * (Supervisor → HR → Management). Reverting here ends the request, unless a
 * higher approver overrides it.
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

        $leave->load(['employee', 'supervisorEmployee', 'supervisorUser', 'comments']);

        return view('portal.team-leave.show', compact('leave') + ['canComment' => true, 'commentUrl' => route('portal.team-leave.comment', $leave)]);
    }

    /** The supervisor comments on their team member's request. */
    public function comment(Request $request, LeaveRequest $leave)
    {
        $this->authorizeSupervisor($request, $leave);
        $data = $request->validate(['body' => 'required|string|max:3000'], ['body.required' => 'Please write a comment before sending.']);

        try {
            $this->workflow->comment($leave, $request->user(), $data['body']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(route('portal.team-leave.show', $leave) . '#comments')->with('success', 'Thank you, your comment has been added and everyone involved has been informed.');
    }

    public function decide(Request $request, LeaveRequest $leave)
    {
        $this->authorizeSupervisor($request, $leave);

        $data = $request->validate([
            'supervisor_decision' => 'required|in:Approved,Rejected',
            'supervisor_comments' => 'nullable|required_if:supervisor_decision,Rejected|string|max:5000',
        ], [
            'supervisor_comments.required_if' => 'Kindly add a short reason, so the applicant understands why it was reverted.',
        ]);

        try {
            $leave = $this->workflow->supervisorDecision($leave, $request->user(), $data['supervisor_decision'], $data['supervisor_comments'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('portal.team-leave.index')
            ->with('success', match ($leave->status) {
                'Rejected' => 'Thank you. You have reverted the request, and the employee and HR have been informed.',
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
