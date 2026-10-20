<?php

namespace App\Http\Controllers;

use App\Models\LeaveApprovalSetting;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Leave Management → Approval chain.
 *
 * The Admin chooses the steps a leave request goes through:
 *   Employee → Supervisor → HR → Director (the last switched-on step is final).
 * HR and the Director can see the chain; only the Admin can change it.
 * Changes apply to requests submitted from now on.
 */
class LeaveApprovalChainController extends Controller
{
    public function edit(Request $request)
    {
        $settings = LeaveApprovalSetting::current()->load(['director', 'editor']);

        return view('dashboard.leave-management.approval-chain', [
            'settings'  => $settings,
            'canEdit'   => $request->user()->hasRole('admin'),
            'directors' => User::whereRaw('LOWER(role) = ?', ['director'])->orderBy('name')->get(),
            'hrCount'   => User::whereRaw('LOWER(role) IN (?, ?)', ['hr', 'admin'])->count(),
            'waiting'   => LeaveRequest::where('status', 'Pending')->selectRaw('current_step, COUNT(*) as n')->groupBy('current_step')->pluck('n', 'current_step'),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403, 'Only the Admin can change the approval chain.');

        $data = $request->validate([
            'supervisor_step'   => 'nullable|boolean',
            'hr_step'           => 'nullable|boolean',
            'director_step'     => 'nullable|boolean',
            'director_min_days' => 'nullable|numeric|min:0|max:365',
            'director_user_id'  => 'nullable|integer|exists:users,id',
        ]);

        $hr = $request->boolean('hr_step');
        $director = $request->boolean('director_step');

        if (! $hr && ! $director) {
            return back()->withInput()->withErrors(['hr_step' => 'Keep HR or Management switched on: one of them must give the final approval.']);
        }

        if (! empty($data['director_user_id'])) {
            $u = User::find($data['director_user_id']);
            if (! $u || ! $u->isDirector()) {
                return back()->withInput()->withErrors(['director_user_id' => 'Pick a user whose role is Management.']);
            }
        }

        LeaveApprovalSetting::current()->update([
            'supervisor_step'   => $request->boolean('supervisor_step'),
            'hr_step'           => $hr,
            'director_step'     => $director,
            'director_min_days' => $director ? (float) ($data['director_min_days'] ?? 0) : 0,
            'director_user_id'  => $director ? ($data['director_user_id'] ?? null) : null,
            'updated_by'        => $request->user()->id,
        ]);

        return back()->with('success', 'Approval chain saved. New leave requests will follow it; requests already on their way keep the chain they started with.');
    }
}
