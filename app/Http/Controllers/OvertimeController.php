<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use Illuminate\Http\Request;

/**
 * HR / admin review of overtime requests submitted through the portal.
 */
class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'Pending');

        $requests = OvertimeRequest::with(['employee', 'reviewer'])
            ->when($status !== 'All', fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->q . '%';
                $q->whereHas('employee', fn ($e) => $e
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('employee_id', 'like', $term));
            })
            ->latest('work_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'Pending'  => OvertimeRequest::where('status', 'Pending')->count(),
            'Approved' => OvertimeRequest::where('status', 'Approved')->count(),
            'Rejected' => OvertimeRequest::where('status', 'Rejected')->count(),
        ];

        return view('dashboard.overtime.index', compact('requests', 'counts', 'status'));
    }

    public function approve(Request $request, OvertimeRequest $overtime)
    {
        if ($overtime->status !== 'Pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $request->validate(['review_comment' => 'nullable|string|max:1000']);

        $overtime->update([
            'status'         => 'Approved',
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
            'review_comment' => $request->review_comment,
        ]);

        return back()->with('success', 'Overtime request approved.');
    }

    public function reject(Request $request, OvertimeRequest $overtime)
    {
        if ($overtime->status !== 'Pending') {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $request->validate(['review_comment' => 'required|string|max:1000']);

        $overtime->update([
            'status'         => 'Rejected',
            'reviewed_by'    => auth()->id(),
            'reviewed_at'    => now(),
            'review_comment' => $request->review_comment,
        ]);

        return back()->with('success', 'Overtime request rejected.');
    }
}
