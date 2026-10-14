<?php

namespace App\Http\Controllers;

use App\Models\AttendanceKiosk;
use App\Models\AttendanceRecord;
use App\Models\AttendanceScan;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $att) {}

    /** Company overview for one day (today by default). */
    public function index(Request $request)
    {
        try {
            $this->att->runIfDue();
        } catch (\Throwable $e) {
            report($e);
        }

        $today = AttendanceService::today();
        $date = $this->date($request->query('date'), $today);
        $dept = $request->query('department') ?: null;

        $day = $this->att->day($date, $dept);
        $trend = $this->att->trend($date->copy()->subDays(20), $date, $dept);

        $byDept = $day['rows']->groupBy(fn ($r) => $r->employee->department ?: 'No department')
            ->map(fn ($g, $n) => ['name' => $n] + $this->att->totals($g))->sortBy('name')->values();

        $scans = AttendanceScan::with(['employee', 'kiosk'])->whereDate('date', $date->toDateString())
            ->latest('scanned_at')->latest('id')->limit(12)->get();

        return view('dashboard.attendance.index', [
            'date'        => $date,
            'isToday'     => $date->isSameDay($today),
            'department'  => $dept,
            'departments' => $this->departments(),
            'day'         => $day,
            'trend'       => $trend,
            'byDept'      => $byDept,
            'scans'       => $scans,
            'kiosks'      => AttendanceKiosk::orderBy('name')->get(),
            'settings'    => $this->att->settings(),
            'lastScanId'  => (int) AttendanceScan::max('id'),
        ]);
    }

    /** Small JSON poll for the overview page while it is open on "today". */
    public function live(Request $request)
    {
        $today = AttendanceService::today();
        $since = (int) $request->query('since', 0);
        $day = $this->att->day($today, $request->query('department') ?: null);

        $new = AttendanceScan::with('employee')->where('id', '>', $since)->whereDate('date', $today->toDateString())
            ->orderBy('id')->limit(20)->get()
            ->map(fn ($s) => [
                'id'     => $s->id,
                'name'   => $s->employee?->full_name ?? 'Unknown code',
                'action' => $s->action,
                'time'   => $s->scanned_at->format('H:i'),
                'msg'    => $s->message,
            ]);

        return response()->json([
            'totals' => $day['totals'],
            'scans'  => $new,
            'last'   => $new->last()['id'] ?? $since,
            'now'    => AttendanceService::now()->format('H:i'),
        ]);
    }

    // ------------------------------------------------------------- register

    public function register(Request $request)
    {
        $today = AttendanceService::today();
        $date = $this->date($request->query('date'), $today);
        if ($date->gt($today)) {
            $date = $today;
        }
        $dept = $request->query('department') ?: null;

        return view('dashboard.attendance.register', [
            'date'        => $date,
            'department'  => $dept,
            'departments' => $this->departments(),
            'day'         => $this->att->day($date, $dept),
            'settings'    => $this->att->settings(),
            'isToday'     => $date->isSameDay($today),
        ]);
    }

    public function registerSave(Request $request)
    {
        $request->validate([
            'date'    => ['required', 'date'],
            'entries' => ['array'],
        ]);
        $date = $this->date($request->input('date'), AttendanceService::today());
        if ($date->gt(AttendanceService::today())) {
            return back()->with('error', 'You can\'t record attendance for a future date.');
        }

        $saved = 0;
        $cleared = 0;
        $errors = [];
        $employees = Employee::whereIn('id', array_keys($request->input('entries', [])))->get()->keyBy('id');

        foreach ($request->input('entries', []) as $id => $e) {
            $emp = $employees[$id] ?? null;
            if (! $emp) {
                continue;
            }
            $inRaw = trim((string) ($e['in'] ?? ''));
            $outRaw = trim((string) ($e['out'] ?? ''));
            $status = $e['status'] ?? '';
            $notes = trim((string) ($e['notes'] ?? ''));

            $changed = $inRaw !== ($e['orig_in'] ?? '') || $outRaw !== ($e['orig_out'] ?? '') || $status !== '' || $notes !== ($e['orig_notes'] ?? '');
            if (! $changed) {
                continue;
            }

            $in = AttendanceService::parseTime($inRaw);
            $out = AttendanceService::parseTime($outRaw);
            if (($inRaw !== '' && ! $in) || ($outRaw !== '' && ! $out)) {
                $errors[] = $emp->full_name . ': time not understood (use e.g. 08:05).';
                continue;
            }
            if ($in && $out && $out <= $in) {
                $errors[] = $emp->full_name . ': time out must be after time in.';
                continue;
            }
            if ($status !== '' && ! array_key_exists($status, AttendanceRecord::STATUSES)) {
                $status = '';
            }

            $existing = AttendanceRecord::where('employee_id', $emp->id)->whereDate('date', $date->toDateString())->first();

            // Everything emptied on an existing row = remove it (back to automatic).
            if (! $in && ! $out && $status === '' && $notes === '') {
                if ($existing) {
                    $existing->delete();
                    $cleared++;
                }
                continue;
            }
            if (! $in && ! $out && $status === '' && $existing && $existing->clock_in === null) {
                $status = $existing->status; // only notes changed on an absence row
            }

            $this->att->saveEntry($emp, $date, $in, $out, $status ?: null, $notes, 'manual', $request->user()?->id);
            $saved++;
        }

        $msg = $saved || $cleared ? "Saved {$saved} " . ($saved === 1 ? 'entry' : 'entries') . ($cleared ? ", cleared {$cleared}" : '') . ' for ' . $date->format('d M Y') . '.' : 'Nothing changed.';

        return redirect()->route('attendance.register', ['date' => $date->toDateString(), 'department' => $request->input('department')])
            ->with($errors ? 'error' : 'success', $errors ? $msg . ' Not saved: ' . implode(' ', $errors) : $msg);
    }

    // -------------------------------------------------------------- summary

    public function summary(Request $request)
    {
        [$from, $to, $month] = $this->month($request->query('month'));
        $dept = $request->query('department') ?: null;
        $rows = $this->att->summary($from, $to, $dept);

        $sort = $request->query('sort', 'name');
        $rows = match ($sort) {
            'rate'   => $rows->sortBy(fn ($r) => $r->rate ?? 101),
            'late'   => $rows->sortByDesc('late'),
            'absent' => $rows->sortByDesc('absent'),
            'hours'  => $rows->sortByDesc('worked'),
            default  => $rows,
        };

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days[] = $d->copy();
        }

        $all = $rows->reduce(fn ($c, $r) => [
            'attended' => $c['attended'] + $r->attended, 'expected' => $c['expected'] + $r->expected,
            'late' => $c['late'] + $r->late, 'absent' => $c['absent'] + $r->absent, 'on_leave' => $c['on_leave'] + $r->on_leave,
            'worked' => $c['worked'] + $r->worked, 'late_minutes' => $c['late_minutes'] + $r->late_minutes,
        ], ['attended' => 0, 'expected' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0, 'worked' => 0, 'late_minutes' => 0]);

        return view('dashboard.attendance.summary', [
            'rows'        => $rows->values(),
            'days'        => $days,
            'month'       => $month,
            'from'        => $from,
            'to'          => $to,
            'department'  => $dept,
            'departments' => $this->departments(),
            'sort'        => $sort,
            'all'         => $all,
        ]);
    }

    public function export(Request $request)
    {
        // Single day: the full register.  Month: one line per employee per day.
        if ($request->filled('date')) {
            $date = $this->date($request->query('date'), AttendanceService::today());

            return response($this->att->reportCsv($date), 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="attendance-' . $date->toDateString() . '.csv"',
            ]);
        }

        [$from, $to, $month] = $this->month($request->query('month'));
        $m = $this->att->matrix($from, $to, $request->query('department') ?: null);

        return response()->streamDownload(function () use ($m) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Employee ID', 'Name', 'Department', 'Date', 'Day', 'Status', 'Time In', 'Time Out', 'Minutes Late', 'Hours Worked', 'Source', 'Notes']);
            foreach ($m['employees'] as $e) {
                foreach ($m['days'] as $day) {
                    $c = $m['cells'][$e->id][$day->toDateString()] ?? null;
                    if (! $c || $c->status === null) {
                        continue;
                    }
                    $r = $c->record;
                    fputcsv($h, [$e->employee_id, $e->full_name, $e->department, $day->toDateString(), $day->format('D'),
                        AttendanceService::meta($c->status)['label'], $r?->in_time, $r?->out_time, $r?->minutes_late,
                        $r ? round($r->worked_minutes / 60, 2) : null, $r?->source_in, $c->note]);
                }
            }
            fclose($h);
        }, "attendance-{$month}.csv", ['Content-Type' => 'text/csv']);
    }

    // ------------------------------------------------------------- employee

    public function employee(Request $request, Employee $employee)
    {
        [$from, $to, $month] = $this->month($request->query('month'));
        $row = $this->att->summary($from, $to, null, $employee->id)->first();

        $scans = AttendanceScan::with('kiosk')->where('employee_id', $employee->id)
            ->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())
            ->latest('scanned_at')->limit(60)->get();

        return view('dashboard.attendance.employee', [
            'employee' => $employee,
            'row'      => $row,
            'month'    => $month,
            'from'     => $from,
            'to'       => $to,
            'scans'    => $scans,
            'today'    => AttendanceService::today(),
        ]);
    }

    /** Kiosk photos are private: served only to signed-in staff. */
    public function photo(Request $request)
    {
        $path = (string) $request->query('path');
        abort_unless(str_starts_with($path, 'attendance/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    // -------------------------------------------------------------- helpers

    private function date(?string $v, Carbon $default): Carbon
    {
        try {
            return $v ? Carbon::parse($v, AttendanceService::tz())->startOfDay() : $default->copy();
        } catch (\Throwable $e) {
            return $default->copy();
        }
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} from, to (capped at today), 'Y-m' */
    private function month(?string $v): array
    {
        $today = AttendanceService::today();
        try {
            $from = $v && preg_match('/^\d{4}-\d{2}$/', $v) ? Carbon::createFromFormat('Y-m-d', $v . '-01', AttendanceService::tz())->startOfDay() : $today->copy()->startOfMonth();
        } catch (\Throwable $e) {
            $from = $today->copy()->startOfMonth();
        }
        $to = $from->copy()->endOfMonth()->startOfDay();
        if ($to->gt($today)) {
            $to = $today->copy();
        }
        if ($from->gt($today)) {
            $from = $today->copy()->startOfMonth();
            $to = $today->copy();
        }

        return [$from, $to, $from->format('Y-m')];
    }

    private function departments(): array
    {
        return Employee::whereNotNull('department')->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department')->all();
    }
}
