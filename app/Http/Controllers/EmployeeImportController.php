<?php

namespace App\Http\Controllers;

use App\Services\EmployeeImporter;
use App\Services\PortalAccounts;
use App\Support\EmployeeFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Bulk import: 1) upload  2) preview & fix  3) confirm.
 * Parsed rows are kept in storage/app/imports/{token}.json between steps.
 */
class EmployeeImportController extends Controller
{
    /** Template / export column order. */
    public const COLUMNS = [
        'employee_id', 'first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'nationality',
        'nrc_no', 'passport_number', 'company_email', 'personal_email', 'primary_phone', 'secondary_phone', 'residential_address',
        'emergency_name', 'emergency_relationship', 'emergency_phone', 'next_of_kin_name', 'next_of_kin_phone', 'next_of_kin_address',
        'position', 'department', 'branch', 'supervisor', 'employment_status', 'employment_nature',
        'contract_start', 'contract_end', 'probation_start', 'probation_end',
        'salary', 'bank_name', 'bank_branch', 'bank_account_no', 'bank_sort_code', 'ssn', 'tpin', 'nhima_no',
        'annual_leave_entitlement', 'leave_balance',
    ];

    public function __construct(private EmployeeImporter $importer) {}

    public function create()
    {
        return view('dashboard.employees.import.create', ['columns' => self::COLUMNS]);
    }

    public function template()
    {
        $samples = [
            ['', 'Mwila', '', 'Banda', '1992-04-17', 'Female', 'Married', 'Zambian', '123456/78/1', '', 'mwila.banda@tradesmartzm.com', 'mwila@gmail.com', '0977123456', '', 'Plot 12, Kansenshi, Ndola',
                'Joseph Banda', 'Husband', '0966123456', 'Joseph Banda', '0966123456', 'Plot 12, Kansenshi, Ndola',
                'Accountant', 'Finance', 'Ndola HQ', '', 'Active', 'PERMANENT', '2024-02-01', '', '2024-02-01', '2024-07-31',
                '8500', 'Zanaco', 'Ndola Main', '1234567890123', '010001', '123456789', '1000123456', '987654321', '24', '18'],
            ['', 'Chilufya', 'K', 'Mulenga', '15/09/1998', 'M', 'Single', 'Zambian', '234567/11/1', '', '', 'chilufya.m@gmail.com', '0966765432', '', 'Kitwe',
                'Ruth Mulenga', 'Mother', '0955111222', 'Ruth Mulenga', '0955111222', 'Kitwe',
                'Driver', 'Operations', 'Kitwe', 'Mwila Banda', 'Active', 'CONTRACT', '01/01/2026', '31/12/2026', '01/01/2026', '31/03/2026',
                '4200', 'Stanbic Bank', 'Kitwe', '9130001234567', '040002', '', '', '', '', ''],
        ];

        return response()->streamDownload(function () use ($samples) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS);
            foreach ($samples as $s) {
                fputcsv($out, $s);
            }
            fclose($out);
        }, 'employee-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Step 2: read + analyse the file, then show the preview. */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:csv,txt,xlsx,xls,ods',
        ], ['file.mimes' => 'Upload a CSV or Excel file (.csv, .xlsx, .xls).']);

        $file = $request->file('file');

        try {
            $rows = $this->importer->read($file->getRealPath(), $file->getClientOriginalExtension());
            $extracted = $this->importer->extract($rows);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $token = Str::random(32);
        Storage::disk('local')->put("imports/{$token}.json", json_encode([
            'user_id'  => $request->user()->id,
            'filename' => $file->getClientOriginalName(),
            'at'       => now()->toDateTimeString(),
            'columns'  => $extracted['columns'],
            'unknown'  => $extracted['unknown'],
            'rows'     => $extracted['rows'],
        ]));

        return redirect()->route('employees.import.show', $token);
    }

    public function showPreview(Request $request, string $token)
    {
        $stored = $this->load($request, $token);
        $mode = $request->input('mode', 'create_update');
        $analysis = $this->importer->analyse($stored['rows'], $mode);

        return view('dashboard.employees.import.preview', [
            'token'    => $token,
            'stored'   => $stored,
            'analysis' => $analysis,
            'mode'     => $mode,
            'filter'   => $request->input('show', 'all'),
            'labels'   => collect(self::COLUMNS)->mapWithKeys(fn ($c) => [$c => EmployeeFields::label($c)]),
        ]);
    }

    /** Step 3: save. Re-analyses so the result matches the current database. */
    public function commit(Request $request, string $token)
    {
        $stored = $this->load($request, $token);
        $opts = $request->validate([
            'mode'                => 'required|in:create_update,create_only',
            'default_entitlement' => 'nullable|numeric|min:0|max:365',
            'portal_accounts'     => 'nullable|boolean',
        ]);

        $analysis = $this->importer->analyse($stored['rows'], $opts['mode']);
        $result = $this->importer->commit($analysis, $opts, $request->user());

        $portal = ['made' => 0, 'emailed' => 0, 'failed' => []];
        if ($request->boolean('portal_accounts')) {
            foreach ($result['created'] as $e) {
                if ($e->employment_status !== 'Active' || ! ($e->company_email ?: $e->personal_email)) {
                    continue;
                }
                try {
                    $r = app(PortalAccounts::class)->create($e, $request->user()->name . ' (bulk import)');
                    $portal['made']++;
                    $portal['emailed'] += $r['emailed'] ? 1 : 0;
                } catch (RuntimeException $ex) {
                    $portal['failed'][] = $e->full_name . ': ' . $ex->getMessage();
                }
            }
        }

        Storage::disk('local')->delete("imports/{$token}.json");

        $msg = sprintf('Import finished: %d added, %d updated, %d skipped.', count($result['created']), $result['updated'], $result['skipped']);
        if ($request->boolean('portal_accounts')) {
            $msg .= " Portal logins: {$portal['made']} created, {$portal['emailed']} emailed.";
        }
        $problems = array_merge($result['errors'], $portal['failed']);

        return redirect()->route('employees.index', ['sort' => 'created_at', 'dir' => 'desc'])
            ->with('success', $msg)
            ->with('error', $problems ? 'Some rows had problems: ' . implode(' | ', array_slice($problems, 0, 10)) : null);
    }

    private function load(Request $request, string $token): array
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32}$/', $token) && Storage::disk('local')->exists("imports/{$token}.json"), 404, 'This import has expired. Please upload the file again.');

        $stored = json_decode(Storage::disk('local')->get("imports/{$token}.json"), true);
        abort_unless(($stored['user_id'] ?? null) === $request->user()->id, 403);

        return $stored;
    }
}
