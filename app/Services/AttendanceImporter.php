<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use RuntimeException;

/**
 * Timesheet import: one row per employee per day.
 *   Employee ID | Employee Name | Department | Date | Day | Time In | Time Out | Status | Notes
 * Blank rows (no times, status or notes) are skipped, so a full template
 * can be uploaded with only some days filled in.
 */
class AttendanceImporter
{
    public const MAX_ROWS = 5000;
    public const MAX_TEMPLATE_DAYS = 62;

    public const HEADERS = ['Employee ID', 'Employee Name', 'Department', 'Date', 'Day', 'Time In', 'Time Out', 'Status', 'Notes'];

    public const STATUS_WORDS = [
        'present' => 'present', 'p' => 'present', 'ontime' => 'present', 'in' => 'present',
        'late' => 'late', 'lt' => 'late',
        'halfday' => 'half_day', 'half' => 'half_day', 'hd' => 'half_day',
        'absent' => 'absent', 'a' => 'absent', 'abs' => 'absent', 'awol' => 'absent',
        'onleave' => 'on_leave', 'leave' => 'on_leave', 'l' => 'on_leave', 'sick' => 'on_leave', 'sickleave' => 'on_leave', 'annualleave' => 'on_leave',
        'holiday' => 'holiday', 'h' => 'holiday', 'publicholiday' => 'holiday', 'ph' => 'holiday',
        'off' => 'off', 'dayoff' => 'off', 'rest' => 'off', 'restday' => 'off', 'weekend' => 'off',
    ];

    private const ALIASES = [
        'employeeid' => 'ein', 'ein' => 'ein', 'empid' => 'ein', 'empno' => 'ein', 'employeeno' => 'ein', 'employeenumber' => 'ein',
        'staffid' => 'ein', 'staffno' => 'ein', 'staffnumber' => 'ein', 'id' => 'ein', 'manno' => 'ein',
        'employeename' => 'name', 'name' => 'name', 'fullname' => 'name', 'employee' => 'name', 'staffname' => 'name',
        'date' => 'date', 'attendancedate' => 'date', 'workdate' => 'date',
        'timein' => 'in', 'in' => 'in', 'clockin' => 'in', 'checkin' => 'in', 'arrival' => 'in', 'arrived' => 'in', 'timearrived' => 'in', 'start' => 'in',
        'timeout' => 'out', 'out' => 'out', 'clockout' => 'out', 'checkout' => 'out', 'departure' => 'out', 'left' => 'out', 'timeleft' => 'out', 'end' => 'out',
        'status' => 'status', 'attendance' => 'status', 'mark' => 'status',
        'notes' => 'notes', 'note' => 'notes', 'remarks' => 'notes', 'remark' => 'notes', 'comment' => 'notes', 'comments' => 'notes', 'reason' => 'notes',
        'department' => 'ignore', 'dept' => 'ignore', 'day' => 'ignore', 'weekday' => 'ignore', 'position' => 'ignore',
    ];

    public function __construct(private AttendanceService $att, private EmployeeImporter $files) {}

    // ------------------------------------------------------------- template

    /** Rows for the downloadable timesheet: every active employee × every working day. */
    public function templateRows(Carbon $from, Carbon $to, ?string $department = null): array
    {
        $m = $this->att->matrix($from, $to, $department);
        $rows = [];
        foreach ($m['days'] as $day) {
            foreach ($m['employees'] as $e) {
                $c = $m['cells'][$e->id][$day->toDateString()] ?? null;
                if (! $c || $c->status === null || ($c->status === 'off' && ! $c->record)) {
                    continue; // not expected that day
                }
                $r = $c->record;
                $prefill = match (true) {
                    $r !== null && ! in_array($r->status, AttendanceRecord::ATTENDED, true) => AttendanceRecord::STATUSES[$r->status]['label'],
                    $r === null && in_array($c->status, ['holiday', 'on_leave'], true) => AttendanceRecord::STATUSES[$c->status]['label'],
                    default => '',
                };
                $rows[] = [
                    $e->employee_id, $e->full_name, $e->department, $day->toDateString(), $day->format('D'),
                    $r?->in_time ?? '', $r?->out_time ?? '', $prefill, $r?->notes ?? ($c->note ?? ''),
                ];
            }
        }

        return $rows;
    }

    public function csv(array $rows): string
    {
        $h = fopen('php://temp', 'r+');
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, self::HEADERS);
        foreach ($rows as $r) {
            fputcsv($h, $r);
        }
        rewind($h);

        return stream_get_contents($h);
    }

    /** Formatted Excel timesheet with a status dropdown and an instructions sheet. */
    public function xlsx(array $rows, string $title, array $settings): string
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            throw new RuntimeException('Excel templates need the maatwebsite/excel package. Download the CSV template instead.');
        }
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sh = $book->getActiveSheet();
        $sh->setTitle('Timesheet');
        $sh->fromArray(self::HEADERS, null, 'A1');
        $n = count($rows) + 1;
        if ($rows) {
            // Write as text so 08:00 stays 08:00 and dates stay YYYY-MM-DD.
            foreach ($rows as $i => $r) {
                foreach ($r as $c => $v) {
                    $sh->setCellValueExplicit([$c + 1, $i + 2], (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }
        $sh->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '185FA5']],
        ]);
        $sh->getStyle("F2:G" . max($n, 500))->getNumberFormat()->setFormatCode('@');
        $sh->getStyle("F1:H{$n}")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFFBEB');
        $sh->getStyle('A1:I1')->getFill()->getStartColor()->setRGB('185FA5');
        foreach (['A' => 14, 'B' => 26, 'C' => 18, 'D' => 12, 'E' => 6, 'F' => 10, 'G' => 10, 'H' => 12, 'I' => 34] as $col => $w) {
            $sh->getColumnDimension($col)->setWidth($w);
        }
        $sh->freezePane('A2');
        $sh->setAutoFilter("A1:I{$n}");

        $list = '"Present,Late,Half day,Absent,On leave,Holiday,Day off"';
        $v = $sh->getCell('H2')->getDataValidation();
        $v->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true)
            ->setShowErrorMessage(true)->setErrorTitle('Status')->setError('Pick a status from the list, or leave it blank.')->setFormula1($list);
        $sh->setDataValidation('H2:H' . max($n, 500), $v);

        $help = $book->createSheet();
        $help->setTitle('How to fill');
        $lines = [
            [$title],
            [''],
            ['1. One row = one employee on one day. Only fill the yellow columns (Time In, Time Out, Status, Notes).'],
            ['2. Times: 24-hour, e.g. 07:55 and 17:05 (8:05 AM also works).'],
            ['3. Leave Status blank when you enter times: Present / Late / Half day is worked out from the times.'],
            ['   Work starts ' . $settings['start'] . ', late after ' . $settings['grace'] . ' min grace, half day = under ' . round($settings['half'] / 60, 1) . ' h worked.'],
            ['4. No times? Pick a Status: Absent, On leave, Holiday, Day off (or Present if the times are unknown).'],
            ['5. Rows left completely empty are ignored, so you can leave days you don\'t know blank.'],
            ['6. Don\'t change Employee ID or Date. You can delete rows or add rows (copy the ID exactly).'],
            ['7. Save and upload under Attendance → Import timesheets. You\'ll see a preview before anything is saved.'],
        ];
        $help->fromArray($lines, null, 'A1');
        $help->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $help->getColumnDimension('A')->setWidth(110);
        $book->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'att');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($tmp);
        $bin = file_get_contents($tmp);
        @unlink($tmp);

        return $bin;
    }

    // ---------------------------------------------------------------- read

    /** @return array{rows: array, columns: array, unknown: array} */
    public function extract(string $path, string $ext): array
    {
        $raw = $this->files->read($path, $ext);
        if (count($raw) < 2) {
            throw new RuntimeException('The file has no data rows. Row 1 must be the column headings.');
        }
        if (count($raw) - 1 > self::MAX_ROWS) {
            throw new RuntimeException('Please import at most ' . self::MAX_ROWS . ' rows per file (e.g. one month at a time).');
        }

        $map = [];
        $unknown = [];
        foreach ($raw[0] as $i => $h) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower((string) $h));
            if ($norm === '') {
                continue;
            }
            $f = self::ALIASES[$norm] ?? null;
            if ($f === null) {
                $unknown[] = trim((string) $h);
            } elseif ($f !== 'ignore' && ! in_array($f, $map, true)) {
                $map[$i] = $f;
            }
        }
        if (! in_array('date', $map, true) || (! in_array('ein', $map, true) && ! in_array('name', $map, true))) {
            throw new RuntimeException('Couldn\'t find the "Employee ID" and "Date" columns. Please use the timesheet template (row 1 = headings).');
        }
        if (! array_intersect(['in', 'out', 'status'], $map)) {
            throw new RuntimeException('Couldn\'t find any "Time In", "Time Out" or "Status" column.');
        }

        $rows = [];
        foreach (array_slice($raw, 1) as $k => $r) {
            $d = [];
            foreach ($map as $i => $f) {
                $v = $r[$i] ?? null;
                $d[$f] = is_string($v) ? trim($v) : $v;
            }
            $rows[] = ['line' => $k + 2, 'data' => $d];
        }

        return ['rows' => $rows, 'columns' => array_values($map), 'unknown' => $unknown];
    }

    // ------------------------------------------------------------- analyse

    /**
     * @param string $mode overwrite | fill (only fill days / times that are still empty)
     */
    public function analyse(array $rows, string $mode = 'overwrite'): array
    {
        $today = AttendanceService::today()->toDateString();
        $employees = Employee::all();
        $byEin = $employees->keyBy(fn ($e) => strtoupper(trim((string) $e->employee_id)));
        $byName = $employees->groupBy(fn ($e) => strtolower(preg_replace('/\s+/', ' ', trim($e->full_name))));

        // Existing records for every date in the file, in one query.
        $dates = collect($rows)->map(fn ($r) => $this->files->parseDate($r['data']['date'] ?? ''))->filter()->unique();
        $existing = $dates->isEmpty() ? collect() : AttendanceRecord::whereDate('date', '>=', $dates->min())->whereDate('date', '<=', $dates->max())->get()
            ->keyBy(fn ($r) => $r->employee_id . '|' . $r->date->toDateString());

        $seen = [];
        $out = [];
        $sum = ['total' => 0, 'create' => 0, 'update' => 0, 'unchanged' => 0, 'skip' => 0, 'error' => 0, 'warnings' => 0];

        foreach ($rows as $row) {
            $d = $row['data'];
            $errors = [];
            $warnings = [];
            $inRaw = trim((string) ($d['in'] ?? ''));
            $outRaw = trim((string) ($d['out'] ?? ''));
            $stRaw = trim((string) ($d['status'] ?? ''));
            $notes = trim((string) ($d['notes'] ?? ''));

            $item = ['line' => $row['line'], 'ein' => $d['ein'] ?? null, 'name' => $d['name'] ?? null, 'date' => null, 'in' => null, 'out' => null,
                'status' => null, 'notes' => $notes, 'employee_id' => null, 'action' => 'skip', 'errors' => [], 'warnings' => [], 'before' => null];

            if ($inRaw === '' && $outRaw === '' && $stRaw === '' && $notes === '') {
                $sum['skip']++;
                continue; // empty template row: ignore silently
            }
            $sum['total']++;

            // employee
            $e = null;
            if (filled($d['ein'] ?? null)) {
                $e = $byEin[strtoupper(trim((string) $d['ein']))] ?? null;
                if (! $e) {
                    $errors[] = 'No employee with ID "' . $d['ein'] . '".';
                }
            } elseif (filled($d['name'] ?? null)) {
                $matches = $byName[strtolower(preg_replace('/\s+/', ' ', trim((string) $d['name'])))] ?? collect();
                if ($matches->count() === 1) {
                    $e = $matches->first();
                    $warnings[] = 'Matched by name. Add the Employee ID to be sure.';
                } else {
                    $errors[] = $matches->count() ? 'More than one employee is called "' . $d['name'] . '". Use the Employee ID.' : 'No employee called "' . $d['name'] . '".';
                }
            } else {
                $errors[] = 'Employee ID is missing.';
            }
            if ($e) {
                $item['employee_id'] = $e->id;
                $item['name'] = $e->full_name;
                $item['ein'] = $e->employee_id;
                if (strtolower((string) $e->employment_status) !== 'active') {
                    $warnings[] = $e->full_name . ' is ' . ($e->employment_status ?: 'not active') . '.';
                }
            }

            // date
            $date = $this->files->parseDate($d['date'] ?? '');
            if (! $date) {
                $errors[] = 'Date "' . ($d['date'] ?? '') . '" not recognised (use YYYY-MM-DD).';
            } elseif ($date > $today) {
                $errors[] = 'Date ' . $date . ' is in the future.';
            }
            $item['date'] = $date;

            // times
            $in = AttendanceService::parseTime($d['in'] ?? null);
            $outT = AttendanceService::parseTime($d['out'] ?? null);
            if ($inRaw !== '' && ! $in) {
                $errors[] = 'Time In "' . $inRaw . '" not understood (use 08:05).';
            }
            if ($outRaw !== '' && ! $outT) {
                $errors[] = 'Time Out "' . $outRaw . '" not understood (use 17:05).';
            }
            if ($in && $outT && $outT <= $in) {
                $errors[] = 'Time Out is not after Time In.';
            }
            $item['in'] = $in;
            $item['out'] = $outT;

            // status
            $status = null;
            if ($stRaw !== '') {
                $status = self::STATUS_WORDS[preg_replace('/[^a-z]/', '', strtolower($stRaw))] ?? null;
                if (! $status) {
                    $errors[] = 'Status "' . $stRaw . '" not recognised (Present, Late, Half day, Absent, On leave, Holiday, Day off).';
                } elseif (($in || $outT) && ! in_array($status, AttendanceRecord::ATTENDED, true)) {
                    $warnings[] = 'Times given with status "' . AttendanceRecord::STATUSES[$status]['label'] . '": the status will be used.';
                }
            }
            $item['status'] = $status;

            if ($e && $date) {
                $key = $e->id . '|' . $date;
                if (isset($seen[$key])) {
                    $errors[] = 'Same employee and date as row ' . $seen[$key] . '.';
                }
                $seen[$key] = $row['line'];
            }

            if ($errors) {
                $item['action'] = 'error';
            } else {
                $rec = $existing[$e->id . '|' . $date] ?? null;
                if (! $in && $outT && ! $rec?->clock_in) {
                    $warnings[] = 'Time Out without Time In.';
                }
                if (! $rec) {
                    $item['action'] = 'create';
                } else {
                    $item['before'] = ['in' => $rec->in_time, 'out' => $rec->out_time, 'status' => $rec->status, 'source' => $rec->source_in];
                    [$newIn, $newOut] = $this->merged($rec, $in, $outT, $status, $mode);
                    $same = substr((string) $newIn, 0, 5) === (string) $rec->in_time && substr((string) $newOut, 0, 5) === (string) $rec->out_time
                        && (! $status || $status === $rec->status) && ($notes === '' || $notes === (string) $rec->notes);
                    if ($same) {
                        $item['action'] = 'unchanged';
                    } elseif ($mode === 'fill' && $rec->clock_in && $rec->clock_out) {
                        $item['action'] = 'unchanged';
                        $warnings[] = 'Already has times: kept (fill-blanks mode).';
                    } else {
                        $item['action'] = 'update';
                        if ($mode === 'overwrite' && in_array($rec->source_in, ['qr', 'biometric'], true) && $in && substr($in, 0, 5) !== $rec->in_time) {
                            $warnings[] = 'Replaces the kiosk scan (' . $rec->in_time . ').';
                        }
                    }
                }
            }

            $item['errors'] = $errors;
            $item['warnings'] = $warnings;
            $sum[$item['action']]++;
            if ($warnings) {
                $sum['warnings']++;
            }
            $out[] = $item;
        }

        return ['rows' => $out, 'summary' => $sum];
    }

    /** @return array{created:int, updated:int, failed:int} */
    public function commit(array $analysis, string $mode, ?User $actor): array
    {
        $res = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $emps = Employee::whereIn('id', collect($analysis['rows'])->pluck('employee_id')->filter()->unique())->get()->keyBy('id');

        foreach ($analysis['rows'] as $r) {
            if (! in_array($r['action'], ['create', 'update'], true) || ! isset($emps[$r['employee_id']])) {
                continue;
            }
            try {
                $rec = AttendanceRecord::where('employee_id', $r['employee_id'])->whereDate('date', $r['date'])->first();
                [$in, $out] = $rec ? $this->merged($rec, $r['in'], $r['out'], $r['status'], $mode) : [$r['in'], $r['out']];
                $this->att->saveEntry($emps[$r['employee_id']], $r['date'], $in, $out, $r['status'], $r['notes'] !== '' ? $r['notes'] : null, 'import', $actor?->id, true);
                $res[$r['action'] === 'create' ? 'created' : 'updated']++;
            } catch (\Throwable $e) {
                report($e);
                $res['failed']++;
            }
        }

        return $res;
    }

    /**
     * Times to save over an existing record.
     *  fill:      keep any time already there, only fill gaps.
     *  overwrite: file wins, but a BLANK cell keeps the existing time —
     *             unless the row sets a non-attendance status (Absent, Leave…).
     */
    private function merged(AttendanceRecord $rec, ?string $in, ?string $out, ?string $status, string $mode): array
    {
        if ($mode === 'fill') {
            return [$rec->clock_in ?: $in, $rec->clock_out ?: $out];
        }
        if ($status && ! in_array($status, AttendanceRecord::ATTENDED, true)) {
            return [$in, $out];
        }

        return [$in ?? $rec->clock_in, $out ?? $rec->clock_out];
    }

    /** Default template range: this week so far (Mon → today). */
    public static function defaultRange(): array
    {
        $t = AttendanceService::today();

        return [$t->copy()->startOfWeek(), $t->copy()];
    }
}
