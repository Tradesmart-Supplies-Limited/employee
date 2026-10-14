<?php

namespace App\Http\Controllers;

use App\Mail\AttendanceDailyReport;
use App\Models\AttendanceKiosk;
use App\Models\AttendanceSetting;
use App\Models\AttendanceShift;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AttendanceSetupController extends Controller
{
    public function __construct(private AttendanceService $att) {}

    // ------------------------------------------------------------ settings

    public function settings()
    {
        return view('dashboard.attendance.settings', [
            'shifts'      => AttendanceShift::ordered(),
            'departments' => \App\Services\DepartmentStats::names(),
            'settings'   => $this->att->settings(),
            'recipients' => $this->att->reportRecipients(),
            'kiosks'     => AttendanceKiosk::count(),
            'tz'         => AttendanceService::tz(),
            'now'        => AttendanceService::now(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $time = ['nullable', 'date_format:H:i'];
        $timeRules = [];
        foreach (array_keys(AttendanceShift::DAY_TYPES) as $d) {
            foreach (['start', 'end'] as $p) {
                $timeRules["shifts.*.{$d}_{$p}"] = $time;
                $timeRules["new_shift.{$d}_{$p}"] = $time;
            }
        }
        $data = $request->validate($timeRules + [
            'shifts'                => ['required', 'array', 'min:1'],
            'shifts.*.name'         => ['required', 'string', 'max:60'],
            'shifts.*.color'        => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'new_shift'             => ['nullable', 'array'],
            'new_shift.name'        => ['nullable', 'string', 'max:60'],
            'new_shift.color'       => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'default_shift'         => ['required', 'integer'],
            'grace_minutes'         => ['required', 'integer', 'min:0', 'max:240'],
            'half_day_minutes'      => ['required', 'integer', 'min:0', 'max:720'],
            'scan_cooldown_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'working_days'          => ['required', 'array', 'min:1'],
            'working_days.*'        => ['integer', 'between:1,7'],
            'report_recipients'     => ['nullable', 'string', 'max:2000'],
            'rota_departments'      => ['nullable', 'array'],
            'rota_departments.*'    => ['string', 'max:120'],
        ], [
            'working_days.required' => 'Pick at least one working day.',
            'date_format'           => 'Use a 24-hour time like 08:00.',
        ]);

        // Each day type: both times or neither, and the end after the start (shifts end before midnight).
        $rows = $data['shifts'];
        if (filled($data['new_shift']['name'] ?? null)) {
            $rows['new'] = $data['new_shift'];
        }
        $errors = [];
        foreach ($rows as $id => $row) {
            foreach (array_keys(AttendanceShift::DAY_TYPES) as $d) {
                $st = $row["{$d}_start"] ?? null;
                $en = $row["{$d}_end"] ?? null;
                if (($st && ! $en) || (! $st && $en)) {
                    $errors["shifts.{$id}.{$d}_end"] = ($row['name'] ?? 'Shift') . ': give both a start and an end for ' . AttendanceShift::DAY_TYPES[$d] . ', or leave both blank.';
                } elseif ($st && $en && $en <= $st) {
                    $errors["shifts.{$id}.{$d}_end"] = ($row['name'] ?? 'Shift') . ': ' . AttendanceShift::DAY_TYPES[$d] . ' must end after it starts.';
                }
            }
            if (empty($row['weekday_start']) && $id === (string) $data['default_shift']) {
                $errors["shifts.{$id}.weekday_start"] = 'The default shift needs Mon – Fri hours.';
            }
        }
        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $emails = collect(preg_split('/[\s,;]+/', (string) ($data['report_recipients'] ?? '')))->map(fn ($e) => strtolower(trim($e)))->filter();
        $bad = $emails->reject(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($bad->isNotEmpty()) {
            return back()->withInput()->withErrors(['report_recipients' => 'Not a valid email: ' . $bad->implode(', ')]);
        }

        $fields = fn ($row) => collect(array_keys(AttendanceShift::DAY_TYPES))->flatMap(fn ($d) => [
            "{$d}_start" => $row["{$d}_start"] ?? null, "{$d}_end" => $row["{$d}_end"] ?? null,
        ])->all();
        foreach ($data['shifts'] as $id => $row) {
            if ($shift = AttendanceShift::find($id)) {
                $shift->update(['name' => $row['name'], 'color' => $row['color'] ?? $shift->color, 'is_active' => ! $request->boolean("shifts.{$id}.archived")] + $fields($row));
            }
        }
        if (filled($data['new_shift']['name'] ?? null)) {
            AttendanceShift::create([
                'name'  => $data['new_shift']['name'],
                'code'  => strtoupper(\Illuminate\Support\Str::limit(preg_replace('/[^A-Za-z0-9]/', '', $data['new_shift']['name']), 12, '')) . '-' . \Illuminate\Support\Str::random(4),
                'color' => $data['new_shift']['color'] ?? '#0f766e',
                'sort'  => (int) AttendanceShift::max('sort') + 1,
            ] + $fields($data['new_shift']));
        }
        $default = AttendanceShift::find($data['default_shift']) ?? AttendanceShift::default();
        AttendanceShift::where('id', '!=', $default->id)->update(['is_default' => false]);
        $default->update(['is_default' => true, 'is_active' => true]);

        $this->att->settings()->update([
            // Kept in step with the default shift for anything that reads "the" working hours.
            'work_start'            => $default->weekday_start,
            'work_end'              => $default->weekday_end,
            'grace_minutes'         => $data['grace_minutes'],
            'half_day_minutes'      => $data['half_day_minutes'],
            'scan_cooldown_minutes' => $data['scan_cooldown_minutes'],
            'working_days'          => array_values(array_unique(array_map('intval', $data['working_days']))),
            'report_recipients'     => $emails->unique()->values()->all(),
            'report_enabled'        => $request->boolean('report_enabled'),
            'report_include_hr'     => $request->boolean('report_include_hr'),
            'report_attach_csv'     => $request->boolean('report_attach_csv'),
            'count_holidays_off'    => $request->boolean('count_holidays_off'),
            'rota_enabled'          => $request->boolean('rota_enabled'),
            'rota_departments'      => array_values(array_unique($data['rota_departments'] ?? [])),
            'overtime_auto'         => $request->boolean('overtime_auto'),
            'comp_off_required'     => $request->boolean('comp_off_required'),
        ]);

        return redirect()->route('attendance.settings')->with('success', 'Attendance settings saved. Shift times and rules apply to new scans and edits from now on.');
    }

    /** Send a sample of the daily report (today so far, or a chosen day) to the signed-in user. */
    public function testReport(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->input('date'), AttendanceService::tz()) : AttendanceService::today();
        $to = $request->user()?->email;
        if (! $to) {
            return back()->with('error', 'Your account has no email address.');
        }

        try {
            Mail::to($to)->send(new AttendanceDailyReport($this->att->reportData($date), $this->att->reportCsv($date), true));
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not send: ' . $e->getMessage());
        }

        return back()->with('success', "Test report for {$date->format('d M Y')} sent to {$to}.");
    }

    /** Close a finished day now and (re)send its report to the full list. */
    public function sendDay(Request $request)
    {
        $request->validate(['date' => ['required', 'date']]);
        $date = Carbon::parse($request->input('date'), AttendanceService::tz())->startOfDay();
        if (! $date->lt(AttendanceService::today())) {
            return back()->with('error', 'Only finished days can be closed. Today closes automatically at midnight.');
        }
        $res = $this->att->closeDay($date);
        $sent = $this->att->sendReport($date, null, true);

        return back()->with($sent ? 'success' : 'error', "Closed {$date->format('d M Y')} ({$res['written']} absence / leave rows added). " . ($sent ? 'Report sent to ' . count($this->att->reportRecipients()) . ' recipient(s).' : 'Report not sent: add recipients and check the mail settings.'));
    }

    // ---------------------------------------------------------- QR & kiosks

    public function qr(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $employees = Employee::whereRaw('LOWER(employment_status) = ?', ['active'])
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")->orWhere('employee_id', 'like', "%{$q}%")))
            ->orderBy('first_name')->paginate(15)->withQueryString();

        return view('dashboard.attendance.qr', [
            'kiosks'      => AttendanceKiosk::orderBy('name')->get(),
            'employees'   => $employees,
            'departments' => Employee::whereNotNull('department')->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department'),
            'q'           => $q,
            'secure'      => $request->secure() || in_array($request->getHost(), ['localhost', '127.0.0.1'], true),
        ]);
    }

    public function storeKiosk(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);
        $k = AttendanceKiosk::create($data + [
            'token'         => AttendanceKiosk::newToken(),
            'is_active'     => true,
            'capture_photo' => $request->boolean('capture_photo', true),
            'created_by'    => $request->user()?->id,
        ]);

        return redirect()->route('attendance.qr')->with('success', "Kiosk \"{$k->name}\" created. Open its link on the entrance tablet.")->with('new_kiosk', $k->id);
    }

    public function updateKiosk(Request $request, AttendanceKiosk $kiosk)
    {
        $data = $request->validate([
            'name'     => ['sometimes', 'required', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);
        if ($request->has('toggle')) {
            $kiosk->update(['is_active' => ! $kiosk->is_active]);

            return back()->with('success', "{$kiosk->name} " . ($kiosk->is_active ? 're-enabled.' : 'switched off. Its link stops working immediately.'));
        }
        if ($request->has('toggle_photo')) {
            $kiosk->update(['capture_photo' => ! $kiosk->capture_photo]);

            return back()->with('success', "Photo capture " . ($kiosk->capture_photo ? 'on' : 'off') . " for {$kiosk->name}.");
        }
        $kiosk->update($data);

        return back()->with('success', 'Kiosk updated.');
    }

    public function regenerateKiosk(AttendanceKiosk $kiosk)
    {
        $kiosk->update(['token' => AttendanceKiosk::newToken(), 'last_seen_at' => null]);

        return back()->with('success', "New link created for {$kiosk->name}. The old link no longer works: open the new one on the tablet.")->with('new_kiosk', $kiosk->id);
    }

    public function destroyKiosk(AttendanceKiosk $kiosk)
    {
        $name = $kiosk->name;
        $kiosk->delete();

        return back()->with('success', "Kiosk \"{$name}\" deleted. Its scans stay in the records.");
    }

    /** Printable badge sheet (one card per employee, cut along the lines). */
    public function badges(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->query('ids', [])));
        $employees = Employee::query()
            ->when($ids, fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q->whereRaw('LOWER(employment_status) = ?', ['active']))
            ->when($request->query('department'), fn ($q, $d) => $q->where('department', $d))
            ->orderBy('first_name')->orderBy('last_name')->get();

        return view('dashboard.attendance.badges', ['employees' => $employees, 'department' => $request->query('department')]);
    }

    /** Cancel an employee's current QR (lost badge) and issue a new one. */
    public function reissue(Employee $employee)
    {
        $employee->forceFill(['attendance_qr_version' => (int) ($employee->attendance_qr_version ?: 1) + 1])->save();

        return back()->with('success', "New QR code issued for {$employee->full_name}. The old badge no longer works. Print a new badge or ask them to open My Attendance in the portal.");
    }
}
