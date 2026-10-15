<?php

namespace App\Services;

use App\Mail\AttendanceDailyReport;
use App\Models\AttendanceKiosk;
use App\Models\AttendanceRecord;
use App\Models\AttendanceScan;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Support\HrRecipients;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Attendance rules in one place.
 *
 *  - Times are LOCAL (config attendance.timezone, Africa/Lusaka by default),
 *    stored as a date + "H:i:s" so nothing shifts with the app's UTC clock.
 *  - First accepted scan of the day = clock in, any later scan = clock out
 *    (the last one wins). Scans within the cool-down are ignored as doubles.
 *  - Late = clock in after start + grace. Half day = worked less than the
 *    half-day minimum. Left early = clocked out before the end time.
 *  - Closing a day (midnight) writes Absent / On leave rows for working days
 *    with no scan, flags missing clock-outs and emails the daily report.
 */
class AttendanceService
{
    /** Extra pseudo-status used only for "today, no scan yet". */
    public const NOT_IN = ['label' => 'Not in yet', 'color' => '#64748b', 'bg' => '#f1f5f9', 'icon' => 'bi-hourglass-split'];

    /** Days before attendance was switched on: never counted as absent. */
    public const UNTRACKED = ['label' => 'Not tracked', 'color' => '#cbd5e1', 'bg' => '#f8fafc', 'icon' => 'bi-dash-circle'];

    private ?string $trackingStart = null;

    private ?AttendanceSetting $settings = null;
    private array $holidayCache = [];

    // ------------------------------------------------------------------ time

    public static function tz(): string
    {
        return config('attendance.timezone', 'Africa/Lusaka');
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::tz());
    }

    public static function today(): Carbon
    {
        return self::now()->startOfDay();
    }

    public function settings(): AttendanceSetting
    {
        return $this->settings ??= AttendanceSetting::current();
    }

    public function refreshSettings(): AttendanceSetting
    {
        $this->schedule = null;

        return $this->settings = AttendanceSetting::current();
    }

    private ?ShiftSchedule $schedule = null;

    public function schedule(): ShiftSchedule
    {
        return $this->schedule ??= new ShiftSchedule($this->settings());
    }

    /** The shift plan for one person on one day (rota or default). */
    public function planFor(Employee $e, Carbon|string $date): object
    {
        $date = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date, self::tz())->startOfDay();

        return $this->schedule()->planFor($e, $date, $this->holidays($date, $date));
    }

    /** Copy the day's plan onto the record (expected times, shift, overtime day…). */
    public function applyPlan(AttendanceRecord $r, Employee $e): object
    {
        $plan = $this->planFor($e, Carbon::parse($r->date instanceof \DateTimeInterface ? $r->date->format('Y-m-d') : $r->date, self::tz()));
        $r->shift_id = $plan->shift?->id;
        $r->scheduled_start = $plan->expected ? $plan->start : null;
        $r->scheduled_end = $plan->expected ? $plan->end : null;
        $r->day_type = $plan->day_type;
        $r->rostered = $plan->rota && $plan->kind === 'shift';

        return $plan;
    }

    /**
     * Sunday / public-holiday work → an overtime claim in the existing overtime
     * workflow. Pre-approved when the person was on the published rota,
     * otherwise left Pending for HR.
     */
    public function syncOvertime(AttendanceRecord $r, ?object $plan = null): void
    {
        if (! $this->settings()->overtime_auto || ! class_exists(\App\Models\OvertimeRequest::class)) {
            return;
        }
        if ($r->overtime_minutes <= 0 || ! $r->clock_in || ! $r->clock_out) {
            return;
        }
        $hours = round($r->overtime_minutes / 60, 2);
        $req = $r->overtime_request_id ? \App\Models\OvertimeRequest::find($r->overtime_request_id) : null;
        if ($req) {
            if ($req->status === 'Pending') {
                $req->update(['start_time' => substr($r->clock_in, 0, 5), 'end_time' => substr($r->clock_out, 0, 5), 'hours' => $hours]);
            }

            return;
        }

        $plan ??= $this->planFor($r->employee ?? Employee::find($r->employee_id), $r->date->format('Y-m-d'));
        $what = $r->day_type === 'holiday' ? 'public holiday' : 'Sunday';
        $rota = $plan->assignment?->rota;
        $pre = $r->rostered && $rota && $rota->published_by;

        $req = \App\Models\OvertimeRequest::create([
            'employee_id'    => $r->employee_id,
            'work_date'      => $r->date->format('Y-m-d'),
            'start_time'     => substr($r->clock_in, 0, 5),
            'end_time'       => substr($r->clock_out, 0, 5),
            'hours'          => $hours,
            'day_type'       => $r->day_type === 'holiday' ? 'public_holiday' : 'sunday',
            'reason'         => $r->rostered
                ? "Rostered {$plan->label} on the {$rota?->department} rota, worked on a {$what} (recorded by attendance)."
                : "Worked on a {$what} (recorded by attendance).",
            'status'         => $pre ? 'Approved' : 'Pending',
            'reviewed_by'    => $pre ? $rota->published_by : null,
            'reviewed_at'    => $pre ? now() : null,
            'review_comment' => $pre ? 'Pre-approved: on the published rota.' : null,
        ]);
        $r->overtime_request_id = $req->id;
        $r->saveQuietly();
    }

    // -------------------------------------------------------------------- QR

    public static function qrSignature(Employee $e): string
    {
        $key = (string) config('app.key');
        $raw = hash_hmac('sha256', strtoupper(trim((string) $e->employee_id)) . '|' . (int) ($e->attendance_qr_version ?: 1), $key, true);

        return substr(rtrim(strtr(base64_encode($raw), '+/', '-_'), '='), 0, 10);
    }

    public static function qrPayload(Employee $e): string
    {
        return config('attendance.qr_prefix', 'TSLATT') . '.' . $e->employee_id . '.' . self::qrSignature($e);
    }

    /**
     * Turn scanned text into an employee.
     *
     * @return array{0: ?Employee, 1: ?string} [employee, error message]
     */
    public function resolveCode(string $code): array
    {
        $code = trim($code);
        $prefix = config('attendance.qr_prefix', 'TSLATT') . '.';

        if ($code === '' || strlen($code) > 120) {
            return [null, 'That code could not be read.'];
        }

        if (stripos($code, $prefix) === 0) {
            $rest = substr($code, strlen($prefix));
            $dot = strrpos($rest, '.');
            if ($dot === false) {
                return [null, 'This QR code is incomplete.'];
            }
            $ein = substr($rest, 0, $dot);
            $sig = substr($rest, $dot + 1);
            $e = $this->findByEin($ein);
            if (! $e) {
                return [null, 'No employee with ID ' . $ein . '.'];
            }
            if (! hash_equals(self::qrSignature($e), $sig)) {
                return [null, 'This badge is no longer valid. Please ask HR for your new QR code.'];
            }

            return [$e, null];
        }

        if (config('attendance.qr_require_signature', true)) {
            return [null, 'QR code not recognised. Use the attendance QR from your badge or portal.'];
        }

        $e = $this->findByEin($code);

        return $e ? [$e, null] : [null, 'No employee with ID ' . $code . '.'];
    }

    private function findByEin(string $ein): ?Employee
    {
        $ein = trim($ein);

        return Employee::where('employee_id', $ein)->first()
            ?? Employee::whereRaw('UPPER(employee_id) = ?', [strtoupper($ein)])->first();
    }

    // ------------------------------------------------------------------ scan

    /**
     * Record a scan from a kiosk (or a future biometric device).
     *
     * @return array{ok:bool, kind:string, action:string, title:string, message:string, name:?string, ein:?string, photo:?string, time:string, status:?string}
     */
    public function scan(string $code, ?AttendanceKiosk $kiosk = null, ?string $photoData = null, string $method = 'qr', ?string $ip = null): array
    {
        $now = self::now();
        $s = $this->settings();
        [$e, $error] = $this->resolveCode($code);

        $base = ['ok' => false, 'kind' => 'error', 'action' => 'rejected', 'name' => null, 'ein' => null, 'photo' => null, 'time' => $now->format('H:i'), 'status' => null];

        if (! $e) {
            $this->logScan(null, $kiosk, $now, 'rejected', $method, $code, $error, null, $ip);

            return $base + ['title' => 'Not recognised', 'message' => $error];
        }

        $base['name'] = $e->full_name;
        $base['ein'] = $e->employee_id;
        $base['photo'] = $e->passport_photo ? asset('storage/' . $e->passport_photo) : null;

        if ($e->is_director) {
            $msg = 'Attendance isn\'t recorded for directors.';
            $this->logScan($e, $kiosk, $now, 'rejected', $method, $code, $msg, null, $ip);

            return array_merge($base, ['ok' => true, 'kind' => 'info', 'title' => 'Welcome, ' . $e->first_name, 'message' => $msg]);
        }

        if (strtolower((string) $e->employment_status) !== 'active') {
            $msg = 'Not an active employee (' . ($e->employment_status ?: 'no status') . '). Please see HR.';
            $this->logScan($e, $kiosk, $now, 'rejected', $method, $code, $msg, null, $ip);

            return array_merge($base, ['title' => $e->first_name . ', please see HR', 'message' => $msg]);
        }

        // Double scan within the cool-down → friendly "already done".
        $cool = max(0, (int) $s->scan_cooldown_minutes);
        $last = AttendanceScan::where('employee_id', $e->id)->whereIn('action', ['in', 'out'])
            ->whereDate('date', $now->toDateString())->latest('scanned_at')->first();

        if ($last && $cool > 0 && Carbon::parse($last->scanned_at->format('Y-m-d H:i:s'), self::tz())->gt($now->copy()->subMinutes($cool))) {
            $msg = 'Already recorded at ' . $last->scanned_at->format('H:i') . '. No need to scan again.';
            $this->logScan($e, $kiosk, $now, 'duplicate', $method, $code, $msg, null, $ip);

            return array_merge($base, ['ok' => true, 'kind' => 'info', 'action' => 'duplicate', 'title' => 'Hi ' . $e->first_name . ', you\'re already ' . ($last->action === 'in' ? 'clocked in' : 'clocked out'), 'message' => $msg]);
        }

        $photo = $this->storePhoto($photoData, $e, $now);
        $source = $method === 'qr' ? 'qr' : 'biometric';

        $record = $this->recordFor($e->id, $now->toDateString());
        $plan = $this->applyPlan($record, $e);

        if (! $record->clock_in) {
            $record->fill(['clock_in' => $now->format('H:i:s'), 'source_in' => $source, 'in_photo' => $photo]);
            $action = 'in';
        } else {
            $record->fill(['clock_out' => $now->format('H:i:s'), 'source_out' => $source, 'out_photo' => $photo]);
            $action = 'out';
        }

        $this->evaluate($record);
        $record->save();
        if ($action === 'out') {
            $this->syncOvertime($record, $plan);
        }

        if ($action === 'in') {
            $title = $this->greeting($now) . ', ' . $e->first_name . '!';
            $msg = 'Clocked IN at ' . $now->format('H:i');
            $kind = 'success';
            if ($record->status === 'late') {
                $msg .= ' · ' . $record->minutes_late . ' min late';
                $kind = 'warning';
            }
            if ($plan->expected && $plan->kind === 'shift' && $plan->start) {
                $msg .= ' · ' . $plan->label;
            } elseif ($plan->kind === 'backup') {
                $msg .= ' · you are backup (on call) today';
            }
            if ($plan->overtime) {
                $msg .= ' · ' . ($plan->day_type === 'holiday' ? 'public holiday' : 'Sunday') . ' work counts as overtime';
            }
            $leave = $this->leaveOn($e->id, $now);
            if ($leave) {
                $msg .= ' · note: you are on ' . $leave . ' today';
            }
        } else {
            $title = 'Goodbye, ' . $e->first_name . '!';
            $msg = 'Clocked OUT at ' . $now->format('H:i') . ' · worked ' . AttendanceRecord::formatMinutes($record->worked_minutes)
                . ($record->overtime_minutes ? ' · overtime recorded' : '');
            $kind = 'out';
        }

        $this->logScan($e, $kiosk, $now, $action, $method, $code, $msg, $photo, $ip);

        return array_merge($base, ['ok' => true, 'kind' => $kind, 'action' => $action, 'title' => $title, 'message' => $msg, 'status' => $record->status]);
    }

    private function greeting(Carbon $t): string
    {
        return $t->hour < 12 ? 'Good morning' : ($t->hour < 17 ? 'Good afternoon' : 'Good evening');
    }

    private function storePhoto(?string $data, Employee $e, Carbon $now): ?string
    {
        if (! $data || ! preg_match('#^data:image/(jpeg|png|webp);base64,#', $data, $m)) {
            return null;
        }
        $bin = base64_decode(substr($data, strpos($data, ',') + 1), true);
        if ($bin === false || strlen($bin) > 400 * 1024 || strlen($bin) < 100) {
            return null;
        }
        $path = 'attendance/' . $now->format('Y/m/d') . '/' . Str::slug($e->employee_id) . '-' . $now->format('His') . '-' . Str::random(6) . '.' . ($m[1] === 'jpeg' ? 'jpg' : $m[1]);

        try {
            Storage::disk('local')->put($path, $bin);

            return $path;
        } catch (\Throwable $ex) {
            Log::warning('Attendance photo not saved: ' . $ex->getMessage());

            return null;
        }
    }

    private function logScan(?Employee $e, ?AttendanceKiosk $k, Carbon $now, string $action, string $method, string $code, ?string $msg, ?string $photo, ?string $ip): void
    {
        AttendanceScan::create([
            'employee_id'         => $e?->id,
            'attendance_kiosk_id' => $k?->id,
            'scanned_at'          => $now->format('Y-m-d H:i:s'),
            'date'                => $now->toDateString(),
            'action'              => $action,
            'method'              => $method,
            'code_hint'           => Str::limit(preg_replace('/\.[^.]+$/', '.…', $code), 36, ''),
            'message'             => Str::limit((string) $msg, 250, ''),
            'photo'               => $photo,
            'ip'                  => $ip,
        ]);
    }

    /** The day's record for an employee, or a new unsaved one (date compared as a date on every database). */
    public function recordFor(int $employeeId, string $date): AttendanceRecord
    {
        return AttendanceRecord::where('employee_id', $employeeId)->whereDate('date', $date)->first()
            ?? new AttendanceRecord(['employee_id' => $employeeId, 'date' => $date]);
    }

    // ----------------------------------------------------------- evaluation

    /**
     * Work out late / worked / half-day / left-early from the times.
     * $forced keeps an explicit HR choice (e.g. "present" = late excused).
     */
    public function evaluate(AttendanceRecord $r, ?string $forced = null): AttendanceRecord
    {
        $s = $this->settings();
        $in = $r->clock_in ? $this->minutesOf($r->clock_in) : null;
        $out = $r->clock_out ? $this->minutesOf($r->clock_out) : null;
        // Expected times: the record's shift (rota or default). Records made before shifts existed
        // (no day_type) fall back to the general hours; days nobody is expected (Sunday, holiday,
        // rota day off) have none, so there's no "late" or "left early".
        if ($r->scheduled_start && $r->scheduled_end) {
            $start = $this->minutesOf($r->scheduled_start);
            $end = $this->minutesOf($r->scheduled_end);
        } elseif ($r->day_type) {
            $start = $end = null;
        } else {
            $start = $this->minutesOf($s->work_start ?: '08:00');
            $end = $this->minutesOf($s->work_end ?: '17:00');
        }

        $r->minutes_late = ($start !== null && $in !== null && $in > $start + (int) $s->grace_minutes) ? $in - $start : 0;
        $r->worked_minutes = ($in !== null && $out !== null && $out > $in) ? $out - $in : 0;
        $r->left_early = $end !== null && $out !== null && $out < $end;
        $r->overtime_minutes = in_array($r->day_type, ['sunday', 'holiday'], true) ? $r->worked_minutes : 0;
        if ($out !== null) {
            $r->missing_out = false;
        }

        if ($in === null) {
            // No clock in: keep whatever "non-attendance" status we were given.
            // No times: an explicit HR status wins, else keep a non-attendance status, else absent.
            if ($forced && array_key_exists($forced, AttendanceRecord::STATUSES)) {
                $r->status = $forced;
            } elseif (! $r->status || in_array($r->status, AttendanceRecord::ATTENDED, true)) {
                $r->status = 'absent';
            }
            $r->minutes_late = 0;

            return $r;
        }

        $computed = $r->minutes_late > 0 ? 'late' : 'present';
        if ($out !== null && $r->worked_minutes < (int) $s->half_day_minutes) {
            $computed = 'half_day';
        }

        $r->status = $forced && array_key_exists($forced, AttendanceRecord::STATUSES) ? $forced : $computed;

        return $r;
    }

    public function minutesOf(string $time): int
    {
        [$h, $m] = array_map('intval', array_pad(explode(':', $time), 2, 0));

        return $h * 60 + $m;
    }

    /** "8:05", "08:05:00", "8.05", "8:05 pm", "0805", Excel 0.336…, "2026-10-01 08:05" → "08:05:00". */
    public static function parseTime($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        // Excel time (0.3368…) or date-time serial (46309.3368…): the fraction is the time of day.
        // ("8.05" typed by a person is 08:05, handled below.)
        if (is_numeric($v) && ((float) $v < 1 || ((float) $v > 1000 && (float) $v < 80000)) && (is_float($v) || preg_match('/\.\d{3,}$/', (string) $v))) {
            $mins = (int) round(fmod((float) $v, 1) * 1440);

            return sprintf('%02d:%02d:00', intdiv($mins, 60) % 24, $mins % 60);
        }
        $v = strtolower(trim((string) $v));
        if (preg_match('/(\d{4}-\d{2}-\d{2})[ t](\d{1,2}:\d{2})/', $v, $m)) {
            $v = $m[2];
        }
        if (preg_match('/^(\d{3,4})$/', $v, $m)) {
            $v = substr($m[1], 0, -2) . ':' . substr($m[1], -2);
        }
        if (! preg_match('/^(\d{1,2})[:.h](\d{2})(?::(\d{2}))?\s*(am|pm|a\.m\.|p\.m\.)?$/', $v, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        $ap = $m[4] ?? '';
        if ($ap !== '') {
            if ($h < 1 || $h > 12) {
                return null;
            }
            $h = str_starts_with($ap, 'p') ? ($h % 12) + 12 : $h % 12;
        }
        if ($h > 23 || $i > 59) {
            return null;
        }

        return sprintf('%02d:%02d:00', $h, $i);
    }

    // ------------------------------------------------------ calendar context

    /** 'Y-m-d' => holiday name, for the years covering $from..$to. */
    public function holidays(Carbon $from, Carbon $to): array
    {
        $key = $from->year . '-' . $to->year;
        if (! isset($this->holidayCache[$key])) {
            try {
                $this->holidayCache[$key] = app(LeaveDayCalculator::class)->holidays($from->year, $to->year);
            } catch (\Throwable $e) {
                $this->holidayCache[$key] = [];
            }
        }

        return $this->holidayCache[$key];
    }

    /** employee_id => [[from, to, type], ...] for approved leave overlapping the range. */
    public function leaveMap(Carbon $from, Carbon $to): array
    {
        $map = [];
        LeaveRequest::where('status', 'Approved')->whereNotNull('employee_id')
            ->whereDate('date_from', '<=', $to->toDateString())
            ->whereDate('date_to', '>=', $from->toDateString())
            ->get(['employee_id', 'date_from', 'date_to', 'leave_type', 'other_leave_type'])
            ->each(function ($l) use (&$map) {
                $map[$l->employee_id][] = [
                    Carbon::parse($l->date_from)->toDateString(),
                    Carbon::parse($l->date_to)->toDateString(),
                    trim(($l->leave_type === 'Other' && $l->other_leave_type ? $l->other_leave_type : $l->leave_type) . ' leave'),
                ];
            });

        return $map;
    }

    public function leaveOn(int $employeeId, Carbon $date): ?string
    {
        $d = $date->toDateString();
        foreach ($this->leaveMap($date, $date)[$employeeId] ?? [] as [$f, $t, $type]) {
            if ($d >= $f && $d <= $t) {
                return $type;
            }
        }

        return null;
    }

    /** Status for someone with NO record on $d (null = not expected that day). */
    private function virtualStatus(Employee $e, string $d, Carbon $date, array $holidays, array $leaves, string $today, array $rotaMap = []): array
    {
        if (strtolower((string) $e->employment_status) !== 'active') {
            return [null, null]; // exited / suspended: only their real records count
        }
        if ($e->contract_start && Carbon::parse($e->contract_start)->toDateString() > $d) {
            return [null, null];
        }
        foreach ($leaves[$e->id] ?? [] as [$f, $t, $type]) {
            if ($d >= $f && $d <= $t) {
                return ['on_leave', $type];
            }
        }

        $plan = $this->schedule()->planFor($e, $date, $holidays, $rotaMap);
        if (! $plan->expected) {
            if ($plan->rota) {
                return ['off', $plan->label];                  // rota day off / backup
            }

            return $plan->day_type === 'holiday' ? ['holiday', $holidays[$d] ?? null] : ['off', null];
        }
        $note = $plan->rota ? $plan->label : null;              // e.g. "Night shift 15:00–22:00"
        if ($d > $today) {
            return [null, null];
        }
        if ($d < $this->trackingStart()) {
            return ['untracked', null];
        }
        if ($d === $today) {
            return ['not_in', $note];
        }

        return ['absent', $note];
    }

    /** First day attendance was recorded (the day the module was set up). */
    public function trackingStart(): string
    {
        return $this->trackingStart ??= ($this->settings()->created_at
            ? $this->settings()->created_at->copy()->setTimezone(self::tz())->toDateString()
            : self::today()->toDateString());
    }

    /** Active employees (optionally one department), sorted by name. Directors are not tracked. */
    public function employees(?string $department = null, ?int $onlyId = null): Collection
    {
        return Employee::query()
            ->when(! $onlyId, fn ($q) => $q->whereRaw('LOWER(employment_status) = ?', ['active'])->tracked())
            ->when($department, fn ($q) => $q->where('department', $department))
            ->when($onlyId, fn ($q) => $q->whereKey($onlyId))
            ->orderBy('first_name')->orderBy('last_name')
            ->get();
    }

    /**
     * Statuses for every employee × day in a range.
     *
     * @return array{employees: Collection, days: array, cells: array, records: Collection}
     *   cells[employee_id][Y-m-d] = (object){status, record, note}
     */
    public function matrix(Carbon $from, Carbon $to, ?string $department = null, ?int $employeeId = null): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        $today = self::today()->toDateString();

        $records = AttendanceRecord::whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($g) => $g->keyBy(fn ($r) => $r->date->toDateString()));

        $employees = $this->employees($department, $employeeId);
        // Include people with records in range who are no longer active.
        $missing = $records->keys()->diff($employees->pluck('id'));
        if ($missing->isNotEmpty()) {
            $employees = $employees->merge(Employee::whereIn('id', $missing)->tracked()
                ->when($department, fn ($q) => $q->where('department', $department))->get())
                ->sortBy(fn ($e) => strtolower($e->first_name . ' ' . $e->last_name))->values();
        }

        $holidays = $this->holidays($from, $to);
        $leaves = $this->leaveMap($from, $to);
        $rotaMap = $this->schedule()->rotaMap($from, $to);
        $days = [];
        foreach (CarbonPeriod::create($from, $to) as $day) {
            $days[] = $day->copy();
        }

        $cells = [];
        foreach ($employees as $e) {
            foreach ($days as $day) {
                $d = $day->toDateString();
                $rec = $records[$e->id][$d] ?? null;
                if ($rec) {
                    $cells[$e->id][$d] = (object) ['status' => $rec->status, 'record' => $rec, 'note' => $rec->notes];
                } else {
                    [$st, $note] = $this->virtualStatus($e, $d, $day, $holidays, $leaves, $today, $rotaMap);
                    $cells[$e->id][$d] = (object) ['status' => $st, 'record' => null, 'note' => $note];
                }
            }
        }

        return ['employees' => $employees, 'days' => $days, 'cells' => $cells, 'records' => $records, 'holidays' => $holidays];
    }

    /** One day: a row per employee plus totals. */
    public function day(Carbon $date, ?string $department = null): array
    {
        $m = $this->matrix($date, $date, $department);
        $d = $date->toDateString();
        $rows = collect();

        foreach ($m['employees'] as $e) {
            $c = $m['cells'][$e->id][$d] ?? null;
            if (! $c || $c->status === null) {
                continue;
            }
            $rows->push((object) ['employee' => $e, 'status' => $c->status, 'record' => $c->record, 'note' => $c->note]);
        }

        return ['rows' => $rows, 'totals' => $this->totals($rows), 'holiday' => $m['holidays'][$d] ?? null, 'working' => $this->settings()->isWorkingDay($date)];
    }

    public function totals(Collection $rows): array
    {
        $count = fn ($s) => $rows->filter(fn ($r) => in_array($r->status, (array) $s, true))->count();
        $t = [
            'present'  => $count('present'),
            'late'     => $count('late'),
            'half_day' => $count('half_day'),
            'absent'   => $count('absent'),
            'not_in'   => $count('not_in'),
            'on_leave' => $count('on_leave'),
            'holiday'  => $count('holiday'),
            'off'      => $count('off'),
            'missing_out' => $rows->filter(fn ($r) => $r->record && $r->record->missing_out)->count(),
        ];
        $t['attended'] = $t['present'] + $t['late'] + $t['half_day'];
        $t['expected'] = $t['attended'] + $t['absent'] + $t['not_in'];
        $t['rate'] = $t['expected'] ? round($t['attended'] / $t['expected'] * 100, 1) : null;
        $t['punctual'] = $t['attended'] ? round(($t['present'] + $t['half_day']) / $t['attended'] * 100, 1) : null;

        return $t;
    }

    public static function meta(?string $status): array
    {
        return $status === 'not_in' ? self::NOT_IN : ($status === 'untracked' ? self::UNTRACKED : (AttendanceRecord::STATUSES[$status] ?? ['label' => '—', 'color' => '#94a3b8', 'bg' => '#f8fafc', 'icon' => 'bi-dash']));
    }

    /** Per-employee totals over a range (monthly summary). */
    public function summary(Carbon $from, Carbon $to, ?string $department = null, ?int $employeeId = null): Collection
    {
        $m = $this->matrix($from, $to, $department, $employeeId);

        return $m['employees']->map(function ($e) use ($m) {
            $cells = collect($m['cells'][$e->id] ?? [])->filter(fn ($c) => $c->status !== null);
            $recs = $cells->pluck('record')->filter();
            $ins = $recs->pluck('clock_in')->filter()->map(fn ($t) => $this->minutesOf($t));
            $c = fn ($s) => $cells->filter(fn ($x) => in_array($x->status, (array) $s, true))->count();
            $attended = $c(AttendanceRecord::ATTENDED);
            $expected = $attended + $c(['absent', 'not_in']);

            return (object) [
                'employee'    => $e,
                'present'     => $c('present'),
                'late'        => $c('late'),
                'half_day'    => $c('half_day'),
                'absent'      => $c('absent'),
                'on_leave'    => $c('on_leave'),
                'attended'    => $attended,
                'expected'    => $expected,
                'rate'        => $expected ? round($attended / $expected * 100) : null,
                'late_minutes' => (int) $recs->sum('minutes_late'),
                'worked'      => (int) $recs->sum('worked_minutes'),
                'avg_in'      => $ins->isNotEmpty() ? sprintf('%02d:%02d', intdiv((int) round($ins->avg()), 60), (int) round($ins->avg()) % 60) : null,
                'missing_out' => $recs->where('missing_out', true)->count(),
                'cells'       => $m['cells'][$e->id] ?? [],
            ];
        });
    }

    /** Daily company totals for a trend chart. */
    public function trend(Carbon $from, Carbon $to, ?string $department = null): array
    {
        $m = $this->matrix($from, $to, $department);
        $out = [];
        foreach ($m['days'] as $day) {
            $d = $day->toDateString();
            $rows = collect($m['employees'])->map(fn ($e) => (object) ['status' => $m['cells'][$e->id][$d]->status ?? null, 'record' => $m['cells'][$e->id][$d]->record ?? null])
                ->filter(fn ($r) => $r->status !== null);
            $t = $this->totals($rows);
            $out[] = ['date' => $day, 'working' => $t['expected'] > 0] + $t;
        }

        return $out;
    }

    // --------------------------------------------------------- manual / import

    /**
     * Save one day for one employee from HR (register or import).
     * Blank in/out with a status (absent, on_leave…) records that status.
     */
    public function saveEntry(Employee $e, Carbon|string $date, ?string $in, ?string $out, ?string $status, ?string $notes, string $source, ?int $userId, bool $overwrite = true): AttendanceRecord
    {
        $date = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $r = $this->recordFor($e->id, $date);

        // Only relabel the source when the time actually changes (a kept kiosk time stays "QR").
        $same = fn ($a, $b) => substr((string) $a, 0, 5) === substr((string) $b, 0, 5);
        if (($overwrite || ! $r->clock_in) && ($in !== null || $overwrite) && ! $same($in, $r->clock_in)) {
            $r->clock_in = $in;
            $r->source_in = $in ? $source : null;
            $r->in_photo = null;
        }
        if (($overwrite || ! $r->clock_out) && ($out !== null || $overwrite) && ! $same($out, $r->clock_out)) {
            $r->clock_out = $out;
            $r->source_out = $out ? $source : null;
            $r->out_photo = null;
        }
        if ($notes !== null && $notes !== '') {
            $r->notes = $notes;
        } elseif ($overwrite) {
            $r->notes = null;
        }

        $r->updated_by = $userId;
        $plan = $this->applyPlan($r, $e);
        $this->evaluate($r, $status ?: null);
        $r->save();
        $this->syncOvertime($r, $plan);

        return $r;
    }

    // ------------------------------------------------------- close & report

    /** Write Absent / On leave rows and flag missing clock-outs for a finished day. */
    public function closeDay(Carbon $date): array
    {
        $d = $date->toDateString();
        $m = $this->matrix($date, $date);
        $written = 0;

        foreach ($m['employees'] as $e) {
            $c = $m['cells'][$e->id][$d] ?? null;
            if (! $c || $c->record || ! in_array($c->status, ['absent', 'on_leave'], true)) {
                continue;
            }
            $r = $this->recordFor($e->id, $d);
            if (! $r->exists) {
                $r->fill(['status' => $c->status, 'source_in' => 'system', 'notes' => $c->note])->save();
            }
            $written++;
        }

        $flagged = AttendanceRecord::whereDate('date', $d)->whereNotNull('clock_in')->whereNull('clock_out')
            ->where('missing_out', false)->update(['missing_out' => true]);

        AttendanceRecord::whereDate('date', $d)->where('overtime_minutes', '>', 0)->whereNull('overtime_request_id')->get()
            ->each(fn ($r) => $this->syncOvertime($r));

        return ['written' => $written, 'missing_out' => $flagged];
    }

    /**
     * Close every finished day not yet closed (normally just yesterday) and
     * send each day's report. Called by the midnight schedule, the kiosk
     * heartbeat and the admin dashboard — whichever runs first after midnight.
     */
    public function runIfDue(bool $force = false): array
    {
        $s = $this->refreshSettings();
        $yesterday = self::today()->subDay();
        $from = $s->last_closed_date ? Carbon::parse($s->last_closed_date->toDateString(), self::tz())->addDay() : $yesterday->copy();
        $floor = self::today()->subDays((int) config('attendance.catch_up_days', 7));
        if ($from->lt($floor)) {
            $from = $floor;
        }
        if (! $force && $from->gt($yesterday)) {
            return [];
        }
        if (! Cache::add('attendance-close-running', true, 300)) {
            return [];
        }

        $done = [];
        try {
            foreach (CarbonPeriod::create($from, $yesterday) as $day) {
                $day = Carbon::parse($day->toDateString(), self::tz());
                $res = $this->closeDay($day);
                $sent = false;
                if ($s->report_enabled) {
                    $sent = $this->sendReport($day);
                }
                $s->update(['last_closed_date' => $day->toDateString()] + ($sent ? ['last_report_at' => now()] : []));
                $done[$day->toDateString()] = $res + ['report' => $sent];
            }
        } finally {
            Cache::forget('attendance-close-running');
        }

        return $done;
    }

    public function reportRecipients(): array
    {
        $s = $this->settings();

        return collect($s->report_recipients ?? [])
            ->merge($s->report_include_hr ? HrRecipients::all() : [])
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()->values()->all();
    }

    /** Everything the daily email shows. */
    public function reportData(Carbon $date): array
    {
        $day = $this->day($date);
        $rows = $day['rows'];
        $byDept = $rows->groupBy(fn ($r) => $r->employee->department ?: 'No department')->map(function ($g, $name) {
            $t = $this->totals($g);

            return ['name' => $name] + $t;
        })->sortBy('name')->values()->all();

        $line = fn ($r) => [
            'name'   => $r->employee->full_name,
            'ein'    => $r->employee->employee_id,
            'dept'   => $r->employee->department,
            'in'     => $r->record?->in_time,
            'out'    => $r->record?->out_time,
            'late'   => $r->record?->minutes_late,
            'hours'  => $r->record ? AttendanceRecord::formatMinutes($r->record->worked_minutes) : null,
            'note'   => $r->note,
        ];

        return [
            'date'        => $date->toDateString(),
            'date_label'  => $date->format('l, d F Y'),
            'totals'      => $day['totals'],
            'holiday'     => $day['holiday'],
            'working'     => $day['working'],
            'departments' => $byDept,
            'absent'      => $rows->where('status', 'absent')->map($line)->values()->all(),
            'late'        => $rows->where('status', 'late')->sortByDesc(fn ($r) => $r->record?->minutes_late)->map($line)->values()->all(),
            'on_leave'    => $rows->where('status', 'on_leave')->map($line)->values()->all(),
            'missing_out' => $rows->filter(fn ($r) => $r->record && $r->record->missing_out)->map($line)->values()->all(),
            'early'       => $rows->filter(fn ($r) => $r->record && $r->record->left_early && $r->record->clock_out)->map($line)->values()->all(),
            'url'         => route('attendance.index', ['date' => $date->toDateString()]),
        ];
    }

    public function reportCsv(Carbon $date): string
    {
        $h = fopen('php://temp', 'r+');
        fputcsv($h, ['Employee ID', 'Name', 'Department', 'Date', 'Status', 'Time In', 'Time Out', 'Minutes Late', 'Hours Worked', 'Missing Clock-out', 'Source', 'Notes']);
        foreach ($this->day($date)['rows'] as $r) {
            fputcsv($h, [
                $r->employee->employee_id, $r->employee->full_name, $r->employee->department, $date->toDateString(),
                self::meta($r->status)['label'], $r->record?->in_time, $r->record?->out_time, $r->record?->minutes_late,
                $r->record ? round($r->record->worked_minutes / 60, 2) : null, $r->record?->missing_out ? 'Yes' : '',
                $r->record?->source_in, $r->note,
            ]);
        }
        rewind($h);

        return stream_get_contents($h);
    }

    /** @return bool sent */
    public function sendReport(Carbon $date, ?array $to = null, bool $force = false): bool
    {
        $to ??= $this->reportRecipients();
        if (empty($to)) {
            Log::warning('Attendance report: no recipients configured.');

            return false;
        }
        $data = $this->reportData($date);

        // Nothing worth sending on a closed day where nobody came in.
        if (! $force && ! $data['working'] && $data['totals']['attended'] === 0) {
            return false;
        }

        try {
            Mail::to($to)->send(new AttendanceDailyReport($data, $this->settings()->report_attach_csv ? $this->reportCsv($date) : null));

            return true;
        } catch (\Throwable $e) {
            Log::error('Attendance report failed: ' . $e->getMessage());

            return false;
        }
    }

    /** Remove kiosk photos older than the retention period. */
    public function prunePhotos(): int
    {
        $days = (int) config('attendance.photo_retention_days', 90);
        if ($days <= 0) {
            return 0;
        }
        $cut = self::today()->subDays($days)->toDateString();
        $n = 0;
        AttendanceScan::whereNotNull('photo')->whereDate('date', '<', $cut)->chunkById(200, function ($scans) use (&$n) {
            foreach ($scans as $sc) {
                Storage::disk('local')->delete($sc->photo);
                $sc->update(['photo' => null]);
                $n++;
            }
        });
        AttendanceRecord::whereDate('date', '<', $cut)->where(fn ($q) => $q->whereNotNull('in_photo')->orWhereNotNull('out_photo'))
            ->update(['in_photo' => null, 'out_photo' => null]);

        return $n;
    }
}
