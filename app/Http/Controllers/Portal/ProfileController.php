<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\ProfileChangeHrMail;
use App\Mail\ProfileChangeReceivedMail;
use App\Models\ProfileChangeRequest;
use App\Support\EmployeeFields;
use App\Support\HrRecipients;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Employee self-service: view everything on their record and update it.
 *   - 'direct' fields (contact, address, family…) apply immediately
 *   - 'approval' fields (legal name, NRC, bank, NAPSA/TPIN/NHIMA…) go to HR
 *     for approval when config('portal.sensitive_changes_require_approval')
 *   - job, salary and contract dates are view-only
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $employee = $request->user()->employee->load(['supervisorEmployee', 'supervisorUser']);

        return view('portal.profile.show', [
            'employee' => $employee,
            'requests' => $employee->changeRequests()->with('reviewer')->limit(10)->get(),
            'pending'  => $employee->changeRequests()->where('status', 'pending')->get(),
        ]);
    }

    public function edit(Request $request)
    {
        $employee = $request->user()->employee;

        return view('portal.profile.edit', [
            'employee'        => $employee,
            'needsApproval'   => (bool) config('portal.sensitive_changes_require_approval', true),
            'pendingFields'   => $employee->changeRequests()->where('status', 'pending')->get()
                ->flatMap(fn ($r) => $r->changes)->map(fn ($c) => $c['new'])->all(),
        ]);
    }

    public function update(Request $request)
    {
        $employee = $request->user()->employee;
        $direct = EmployeeFields::portal('direct');
        $sensitive = EmployeeFields::portal('approval');

        $rules = collect(EmployeeFields::rules())->only(array_merge($direct, $sensitive))->all();
        $rules['first_name'] = 'required|string|max:100';
        $rules['last_name'] = 'required|string|max:100';
        $rules['passport_photo'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096';
        $rules['change_note'] = 'nullable|string|max:1000';

        $data = $request->validate($rules);

        $norm = fn ($v) => $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : (is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v);
        $diff = function (array $fields) use ($data, $employee, $norm) {
            $out = [];
            foreach ($fields as $f) {
                if (! array_key_exists($f, $data)) {
                    continue;
                }
                $old = $norm($employee->$f);
                $new = $norm($data[$f]);
                if ((EmployeeFields::get($f)['type'] ?? null) === 'date' && $new) {
                    $new = Carbon::parse($new)->toDateString();
                }
                if ((string) $old !== (string) $new) {
                    $out[$f] = ['old' => $old, 'new' => $new];
                }
            }

            return $out;
        };

        $directChanges = $diff($direct);
        $sensitiveChanges = $diff($sensitive);
        $needsApproval = (bool) config('portal.sensitive_changes_require_approval', true);

        // 1) Direct changes
        foreach ($directChanges as $f => $c) {
            $employee->$f = $c['new'];
        }
        if (! $needsApproval) {
            foreach ($sensitiveChanges as $f => $c) {
                $employee->$f = $c['new'];
            }
        }
        if ($request->hasFile('passport_photo')) {
            if ($employee->passport_photo) {
                Storage::disk('public')->delete($employee->passport_photo);
            }
            $employee->passport_photo = $request->file('passport_photo')->store('uploads/employees/passports', 'public');
            $directChanges['passport_photo'] = ['old' => null, 'new' => 'new photo'];
        }
        $employee->save();

        // 2) Sensitive changes → request for HR (merged into an open one)
        $messages = [];
        $req = null;
        if ($sensitiveChanges && $needsApproval) {
            $open = $employee->changeRequests()->where('status', 'pending')->first();
            if ($open) {
                $open->update([
                    'changes' => array_merge($open->changes, $sensitiveChanges),
                    'note'    => $data['change_note'] ?? $open->note,
                ]);
                $req = $open;
            } else {
                $req = ProfileChangeRequest::create([
                    'employee_id'  => $employee->id,
                    'requested_by' => $request->user()->id,
                    'changes'      => $sensitiveChanges,
                    'note'         => $data['change_note'] ?? null,
                ]);
            }
            $messages[] = count($sensitiveChanges) . ' change(s) to ' . collect(array_keys($sensitiveChanges))->map(fn ($f) => EmployeeFields::label($f))->implode(', ') . ' sent to HR for approval';
        }

        // 3) Emails: HR sees every change (and what needs approval); the employee gets a receipt.
        $pending = $needsApproval ? $sensitiveChanges : [];
        $applied = $needsApproval ? $directChanges : $directChanges + $sensitiveChanges;
        if ($applied || $pending) {
            $note = $req?->note ?? ($data['change_note'] ?? null);
            $this->tellHr(new ProfileChangeHrMail($employee, 'submitted', $applied, $pending, $req, $note));
            $this->tellEmployee($employee, new ProfileChangeReceivedMail($employee, $applied, $pending, $req));
        }

        if ($directChanges) {
            array_unshift($messages, count($directChanges) . ' detail(s) updated');
        }

        return redirect()->route('portal.profile.show')->with('success', $messages
            ? ucfirst(implode('; ', $messages)) . '.'
            : 'Nothing changed.');
    }

    /** Employee withdraws their pending request. */
    public function cancelRequest(Request $request, ProfileChangeRequest $change)
    {
        abort_unless($change->employee_id === $request->user()->employee_id && $change->status === 'pending', 404);
        $change->update(['status' => 'cancelled']);
        $this->tellHr(new ProfileChangeHrMail($change->employee, 'withdrawn', [], $change->changes, $change));

        return back()->with('success', 'Your change request was withdrawn.');
    }

    /** One email per HR address (no one sees the others' addresses). */
    private function tellHr(ProfileChangeHrMail $mail): void
    {
        foreach (HrRecipients::all() as $to) {
            try {
                Mail::to($to)->queue(clone $mail);
            } catch (\Throwable $e) {
                Log::error('Profile change HR email failed: ' . $e->getMessage());
            }
        }
    }

    private function tellEmployee($employee, $mail): void
    {
        if (! $to = $employee->contact_email) {
            return;
        }
        try {
            Mail::to($to)->queue($mail);
        } catch (\Throwable $e) {
            Log::error('Profile change receipt email failed: ' . $e->getMessage());
        }
    }
}
