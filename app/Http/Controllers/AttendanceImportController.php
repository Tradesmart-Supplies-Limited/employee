<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\AttendanceImporter;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AttendanceImportController extends Controller
{
    public function __construct(private AttendanceImporter $importer, private AttendanceService $att) {}

    public function create()
    {
        [$from, $to] = AttendanceImporter::defaultRange();

        return view('dashboard.attendance.import.create', [
            'from'        => $from,
            'to'          => $to,
            'departments' => Employee::whereNotNull('department')->where('department', '!=', '')->distinct()->orderBy('department')->pluck('department'),
            'excel'       => class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class),
        ]);
    }

    public function template(Request $request)
    {
        $data = $request->validate([
            'from'       => ['required', 'date'],
            'to'         => ['required', 'date', 'after_or_equal:from'],
            'department' => ['nullable', 'string', 'max:120'],
            'format'     => ['nullable', 'in:csv,xlsx'],
        ]);
        $from = Carbon::parse($data['from'], AttendanceService::tz())->startOfDay();
        $to = Carbon::parse($data['to'], AttendanceService::tz())->startOfDay();
        if ($from->diffInDays($to) + 1 > AttendanceImporter::MAX_TEMPLATE_DAYS) {
            return back()->withInput()->with('error', 'Pick at most ' . AttendanceImporter::MAX_TEMPLATE_DAYS . ' days per timesheet.');
        }

        $rows = $this->importer->templateRows($from, $to, $data['department'] ?? null);
        $name = 'timesheet-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . (($data['department'] ?? null) ? '-' . Str::slug($data['department']) : '');

        if (($data['format'] ?? 'xlsx') === 'xlsx') {
            try {
                $s = $this->att->settings();
                $bin = $this->importer->xlsx($rows, 'Timesheet ' . $from->format('d M Y') . ' – ' . $to->format('d M Y') . (($data['department'] ?? null) ? ' · ' . $data['department'] : ''), ['start' => $s->work_start, 'grace' => $s->grace_minutes, 'half' => $s->half_day_minutes]);

                return response($bin, 200, [
                    'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="' . $name . '.xlsx"',
                ]);
            } catch (RuntimeException $e) {
                // fall through to CSV
            }
        }

        return response($this->importer->csv($rows), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '.csv"',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls,ods',
        ], ['file.mimes' => 'Upload the timesheet as CSV or Excel (.csv, .xlsx, .xls).']);

        $file = $request->file('file');
        try {
            $ex = $this->importer->extract($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $token = Str::random(32);
        Storage::disk('local')->put("imports/attendance-{$token}.json", json_encode([
            'user_id'  => $request->user()->id,
            'filename' => $file->getClientOriginalName(),
            'columns'  => $ex['columns'],
            'unknown'  => $ex['unknown'],
            'rows'     => $ex['rows'],
        ]));

        return redirect()->route('attendance.import.show', $token);
    }

    public function show(Request $request, string $token)
    {
        $stored = $this->load($request, $token);
        $mode = $request->query('mode') === 'fill' ? 'fill' : 'overwrite';

        return view('dashboard.attendance.import.preview', [
            'token'    => $token,
            'stored'   => $stored,
            'mode'     => $mode,
            'analysis' => $this->importer->analyse($stored['rows'], $mode),
            'filter'   => $request->query('show', 'all'),
        ]);
    }

    public function commit(Request $request, string $token)
    {
        $stored = $this->load($request, $token);
        $mode = $request->input('mode') === 'fill' ? 'fill' : 'overwrite';
        $analysis = $this->importer->analyse($stored['rows'], $mode);
        $res = $this->importer->commit($analysis, $mode, $request->user());
        Storage::disk('local')->delete("imports/attendance-{$token}.json");

        $dates = collect($analysis['rows'])->whereIn('action', ['create', 'update'])->pluck('date')->filter();
        $msg = "Timesheet imported: {$res['created']} new, {$res['updated']} updated" . ($analysis['summary']['error'] ? ", {$analysis['summary']['error']} row(s) with errors skipped" : '') . ($res['failed'] ? ", {$res['failed']} failed" : '') . '.';

        return redirect()->route('attendance.summary', ['month' => $dates->max() ? substr($dates->max(), 0, 7) : null])->with('success', $msg);
    }

    private function load(Request $request, string $token): array
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32}$/', $token) && Storage::disk('local')->exists("imports/attendance-{$token}.json"), 404, 'This import has expired. Please upload the file again.');
        $stored = json_decode(Storage::disk('local')->get("imports/attendance-{$token}.json"), true);
        abort_unless(($stored['user_id'] ?? null) === $request->user()->id, 403);

        return $stored;
    }
}
