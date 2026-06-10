<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

use App\Mail\LeaveSubmittedMail;
use App\Mail\LeaveApprovedMail;
use App\Mail\LeaveRejectedMail;
use App\Mail\LeaveAdminNotificationMail;
use App\Mail\LeaveForwardedToHRMail;

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

        /*
        |--------------------------------------------------------------------------
        | Calculate Total Leave Days
        |--------------------------------------------------------------------------
        */
        $from = Carbon::parse($request->date_from);
        $to   = Carbon::parse($request->date_to);

        $totalDays = $from->diffInDays($to) + 1;

        /*
        |--------------------------------------------------------------------------
        | Upload Supporting Document
        |--------------------------------------------------------------------------
        */
        $documentPath = null;

        if ($request->hasFile('supporting_document')) {

            $documentPath = $request
                ->file('supporting_document')
                ->store('leave-documents', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Create Leave Request
        |--------------------------------------------------------------------------
        */
        $leave = LeaveRequest::create([

            // Employee
            'employee_name'           => $request->employee_name,
            'employee_number'         => $request->employee_number,
            'position'                => $request->position,
            'email'                   => $request->email,

            // Leave
            'leave_type'              => $request->leave_type,
            'other_leave_type'        => $request->other_leave_type,

            'date_from'               => $request->date_from,
            'date_to'                 => $request->date_to,
            'return_date'             => $request->return_date,

            'total_days'              => $totalDays,

            'reason'                  => $request->reason,
            'late_application_reason' => $request->late_application_reason,

            // Applicant
            'applicant_signature_date'=> now(),

            // Status
            'status'                  => 'Pending',

            // Document
            'supporting_document'     => $documentPath,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Email Applicant
        |--------------------------------------------------------------------------
        */
        Mail::to($leave->email)
            ->send(new LeaveSubmittedMail($leave));

        /*
        |--------------------------------------------------------------------------
        | Notify Admin
        |--------------------------------------------------------------------------
        |
        | Replace with HR email later
        |
        */
        Mail::to('katongobupe444@gmail.com')
            ->send(new LeaveAdminNotificationMail($leave));

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
     */
    public function approve(Request $request, LeaveRequest $leave)
    {
        $leave->update([

            'status'                    => 'Approved',

            // 'supervisor_name'           => auth()->user()->name,
            // 'supervisor_position'       => auth()->user()->role ?? 'Manager',

            // 'supervisor_signature_date' => now(),

            // 'supervisor_comments'       => $request->comments,

            /*
            |--------------------------------------------------------------------------
            | HR Section
            |--------------------------------------------------------------------------
            */
            'days_accrued'              => $request->days_accrued,
            'days_available'            => $request->days_available,
            'days_requested'            => $leave->total_days,
            'days_balance'              => $request->days_balance,

            'hr_name'                   => auth()->user()->name,
            'hr_position'               => auth()->user()->role ?? 'HR Officer',
            'hr_signature_date'         => now(),
        ]);

        Mail::to($leave->email)
            ->send(new LeaveApprovedMail($leave));

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

            // 'supervisor_name'           => auth()->user()->name,
            // 'supervisor_position'       => auth()->user()->role ?? 'Manager',

            'supervisor_signature_date' => now(),
        ]);

        Mail::to($leave->email)
            ->send(new LeaveRejectedMail($leave));

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

    public function updateSupervisor(Request $request, LeaveRequest $leave)
{
    $request->validate([
        'supervisor_decision' => 'nullable|string',
        'supervisor_comments' => 'nullable|string',
        'supervisor_name' => 'nullable|string',
        'supervisor_position' => 'nullable|string',
    ]);

    $leave->update([
        'supervisor_decision' => $request->supervisor_decision,
        'supervisor_comments' => $request->supervisor_comments,
        'supervisor_name' => $request->supervisor_name,
        'supervisor_position' => $request->supervisor_position,
    ]);

    // If approved → send to HR
    Mail::to('bupe@tradesmartzm.com')->send(
        new LeaveForwardedToHRMail($leave)
    );

    return back()->with('success', 'Supervisor decision saved successfully.');
}

public function updateHr(Request $request, LeaveRequest $leave)
{
    $request->validate([
        'days_accrued' => 'nullable|integer',
        'days_available' => 'nullable|integer',
        'days_requested' => 'nullable|integer',
        'days_balance' => 'nullable|integer',
        'hr_name' => 'nullable|string',
        'hr_position' => 'nullable|string',
    ]);

    $leave->update([
        'days_accrued' => $request->days_accrued,
        'days_available' => $request->days_available,
        'days_requested' => $request->days_requested,
        'days_balance' => $request->days_balance,
        'hr_name' => $request->hr_name,
        'hr_position' => $request->hr_position,
    ]);

    LeaveController::approve($request, $leave);

    return back()->with('success', 'HR record updated successfully.');
}

public function print($id)
{
    $leave = LeaveRequest::findOrFail($id);

    return view('dashboard.leave.print', compact('leave'));
}
}