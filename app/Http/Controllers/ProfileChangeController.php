<?php

namespace App\Http\Controllers;

use App\Mail\ProfileChangeDecisionMail;
use App\Models\ProfileChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** HR: approve / reject sensitive detail changes employees made in the portal. */
class ProfileChangeController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.profile-changes.index', [
            'pending' => ProfileChangeRequest::with(['employee', 'requester'])->where('status', 'pending')->oldest()->get(),
            'recent'  => ProfileChangeRequest::with(['employee', 'reviewer'])->where('status', '!=', 'pending')->latest('updated_at')->limit(20)->get(),
        ]);
    }

    public function approve(Request $request, ProfileChangeRequest $change)
    {
        $data = $request->validate(['review_note' => 'nullable|string|max:1000', 'fields' => 'nullable|array']);

        if ($change->status !== 'pending') {
            return back()->with('error', 'This request was already handled.');
        }

        // HR may untick individual fields; those are dropped from the request.
        $keep = $request->has('fields') ? array_intersect_key($change->changes, array_flip($data['fields'] ?? [])) : $change->changes;

        if (! $keep) {
            return back()->with('error', 'Tick at least one change to approve, or reject the request.');
        }

        DB::transaction(function () use ($change, $keep, $data, $request) {
            $employee = $change->employee()->lockForUpdate()->first();
            foreach ($keep as $field => $c) {
                $employee->$field = $c['new'];
            }
            $employee->save();

            $change->update([
                'changes'     => $keep,
                'status'      => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
            ]);
        });

        $this->notify($change);

        return back()->with('success', "Approved: {$change->employee->full_name}'s record is updated and they've been emailed.");
    }

    public function reject(Request $request, ProfileChangeRequest $change)
    {
        $data = $request->validate(['review_note' => 'required|string|max:1000'], ['review_note.required' => 'Please say why, so the employee knows what to do.']);

        if ($change->status !== 'pending') {
            return back()->with('error', 'This request was already handled.');
        }

        $change->update([
            'status'      => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);

        $this->notify($change);

        return back()->with('success', 'Rejected. The employee has been emailed the reason.');
    }

    /** Employee gets the decision; the rest of the HR team gets a copy for the record. */
    private function notify(ProfileChangeRequest $change): void
    {
        $change = $change->fresh(['employee', 'reviewer']);
        if ($to = $change->employee->contact_email) {
            try {
                Mail::to($to)->queue(new ProfileChangeDecisionMail($change));
            } catch (\Throwable $e) {
                Log::error('Profile decision email failed: ' . $e->getMessage());
            }
        }

        $copy = new \App\Mail\ProfileChangeHrMail($change->employee, 'decided', [], $change->changes, $change, $change->note, $change->reviewer?->name);
        foreach (\App\Support\HrRecipients::all([$change->reviewer?->email]) as $hr) {
            try {
                Mail::to($hr)->queue(clone $copy);
            } catch (\Throwable $e) {
                Log::error('Profile decision HR copy failed: ' . $e->getMessage());
            }
        }
    }
}
