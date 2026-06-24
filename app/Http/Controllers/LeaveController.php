<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Jobs\SendLeaveSubmittedEmail;
use App\Jobs\SendLeaveApprovedEmail;
use App\Jobs\SendLeaveRejectedEmail;
use App\Jobs\SendLeaveAdminNotificationEmail;
use App\Jobs\SendLeaveForwardedToHREmail;

class LeaveController extends Controller
{
    /**
     * Public Leave Application Form
     */
    public function create()
    {
        return view('dashboard.leave.apply');
    }

    /**
     * Store Leave Request
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
        $totalDays = $from->diffInDays($to) + 1;

        $documentPath = null;

        if ($request->hasFile('supporting_document')) {
            $documentPath = $request
                ->file('supporting_document')
                ->store('leave-documents', 'public');
        }

        $leave = LeaveRequest::create([
            'employee_name'           => $request->employee_name,
            'employee_number'         => $request->employee_number,
            'position'                => $request->position,
            'email'                   => $request->email,

            'leave_type'              => $request->leave_type,
            'other_leave_type'        => $request->other_leave_type,

            'date_from'               => $request->date_from,
            'date_to'                 => $request->date_to,
            'return_date'             => $request->return_date,

            'total_days'              => $totalDays,

            'reason'                  => $request->reason,
            'late_application_reason' => $request->late_application_reason,

            'applicant_signature_date'=> now(),

            'status'                  => 'Pending',

            'supporting_document'     => $documentPath,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Email Applicant + Notify Admin — both queued, neither blocks the
        | redirect. Admin recipient now comes from config('company.hr_emails')
        | instead of a hardcoded personal address.
        |--------------------------------------------------------------------------
        */
        SendLeaveSubmittedEmail::dispatch($leave->id);
        SendLeaveAdminNotificationEmail::dispatch($leave->id);

        return redirect()
            ->back()
            ->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Leave Dashboard
     */
    public function index()
    {
        $applications = LeaveRequest::latest()->paginate(20);

        return view('dashboard.leave.index', compact('applications'));
    }

    /**
     * View Leave Request
     */
    public function show(LeaveRequest $leave)
    {
        return view('dashboard.leave.show', compact('leave'));
    }

    /**
     * Approve Leave
     |
     | This is the FINAL HR approval step. It sets status to Approved and
     | sends the approval email. Called directly from a dedicated
     | "Approve" action — NOT called internally by updateHr() anymore
     | (see note there for why that coupling was removed).
     */
    public function approve(Request $request, LeaveRequest $leave)
    {
        $leave->update([
            'status'             => 'Approved',

            'days_accrued'       => $request->days_accrued,
            'days_available'     => $request->days_available,
            'days_requested'     => $leave->total_days,
            'days_balance'       => $request->days_balance,

            'hr_name'            => auth()->user()->name,
            'hr_position'        => auth()->user()->role ?? 'HR Officer',
            'hr_signature_date'  => now(),
        ]);

        SendLeaveApprovedEmail::dispatch($leave->id);

        return redirect()
            ->back()
            ->with('success', 'Leave approved successfully.');
    }

    /**
     * Reject Leave
     */
    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        $leave->update([
            'status'                    => 'Rejected',
            'supervisor_comments'       => $request->reason,
            'supervisor_signature_date' => now(),
        ]);

        SendLeaveRejectedEmail::dispatch($leave->id);

        return redirect()
            ->back()
            ->with('success', 'Leave request rejected.');
    }

    /**
     * Delete Leave Request
     */
    public function destroy(LeaveRequest $leave)
    {
        $leave->delete();

        return redirect()
            ->route('leave.index')
            ->with('success', 'Leave request deleted.');
    }

    /**
     * Supervisor decision — forwards to HR for final approval.
     */
    public function updateSupervisor(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'supervisor_decision'  => 'nullable|string',
            'supervisor_comments'  => 'nullable|string',
            'supervisor_name'      => 'nullable|string',
            'supervisor_position'  => 'nullable|string',
        ]);

        $leave->update([
            'supervisor_decision'  => $request->supervisor_decision,
            'supervisor_comments'  => $request->supervisor_comments,
            'supervisor_name'      => $request->supervisor_name,
            'supervisor_position'  => $request->supervisor_position,
        ]);

        // Notify HR — recipient now from config, not a hardcoded address
        SendLeaveForwardedToHREmail::dispatch($leave->id);

        return back()->with('success', 'Supervisor decision saved successfully.');
    }

    /**
     * HR records the leave balance figures for this request.
     |
     | IMPORTANT — this previously called LeaveController::approve()
     | internally, which:
     |   1. Re-ran approve()'s validation/update logic on every HR save,
     |      even if HR was just updating balances without approving yet
     |   2. Overwrote 'days_requested' with $leave->total_days, silently
     |      discarding the days_requested value HR had just submitted
     |      in THIS request
     |   3. Sent a second "Leave Approved" email even if this was just
     |      a balance-figures save, not an actual approval action
     |
     | This method now ONLY saves the HR balance fields. Approving the
     | leave (status change + approval email) is a separate, explicit
     | action — call approve() directly from its own "Approve" button
     | in the UI, not through this method.
     */
    public function updateHr(Request $request, LeaveRequest $leave)
    {
        $request->validate([
            'days_accrued'    => 'nullable|integer',
            'days_available'  => 'nullable|integer',
            'days_requested'  => 'nullable|integer',
            'days_balance'    => 'nullable|integer',
            'hr_name'         => 'nullable|string',
            'hr_position'     => 'nullable|string',
        ]);

        $leave->update([
            'days_accrued'    => $request->days_accrued,
            'days_available'  => $request->days_available,
            'days_requested'  => $request->days_requested,
            'days_balance'    => $request->days_balance,
            'hr_name'         => $request->hr_name,
            'hr_position'     => $request->hr_position,
        ]);

        return back()->with('success', 'HR record updated successfully.');
    }

    public function print($id)
    {
        $leave = LeaveRequest::findOrFail($id);

        return view('dashboard.leave.print', compact('leave'));
    }
}