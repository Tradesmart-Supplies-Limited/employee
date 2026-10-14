<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveWorkflow;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Portal: leave requests from the people who report to the logged-in employee.
 * The supervisor recommends; HR / Admin makes the final decision.
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
            'supervisor_comments.required_if' => 'Please give a reason when recommending rejection.',
        ]);

        try {
            $this->workflow->supervisorDecision($leave, $request->user(), $data['supervisor_decision'], $data['supervisor_comments'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('portal.team-leave.index')
            ->with('success', 'Your decision has been recorded and sent to HR for final approval.');
    }

    private function authorizeSupervisor(Request $request, LeaveRequest $leave): void
    {
        abort_unless(
            $leave->supervisor_employee_id && $leave->supervisor_employee_id === $request->user()->employee_id,
            404
        );
    }
}
