<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\ProfileUpdateHrMail;
use App\Support\EmployeeFields;
use App\Support\HrRecipients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Portal: the employee's own documents (HR-uploaded and their own). */
class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user()->employee;

        return view('portal.documents.index', [
            'employee'   => $employee,
            'documents'  => $employee->documents->sortByDesc(fn ($d) => $d->uploaded_at)->values(),
            'categories' => EmployeeFields::DOC_CATEGORIES,
            'canDelete'  => (bool) config('portal.employees_can_delete_own_documents', true),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'files'    => 'required|array|max:10',
            'files.*'  => 'file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:10240',
            'name'     => 'nullable|string|max:150',
            'category' => ['required', Rule::in(EmployeeFields::DOC_CATEGORIES)],
        ], ['files.*.mimes' => 'Only PDF, image, Word or Excel files can be uploaded.', 'files.*.max' => 'Each file must be 10 MB or smaller.']);

        $employee = $request->user()->employee;
        $added = [];
        $files = $request->file('files');

        foreach ($files as $i => $file) {
            $name = $request->filled('name') ? $request->name . (count($files) > 1 ? ' (' . ($i + 1) . ')' : '') : null;
            $added[] = $employee->attachDocument($file, $name, $request->category, 'employee');
        }

        if (config('portal.notify_hr_on_upload', true)) {
            foreach (HrRecipients::all() as $to) {
                try {
                    Mail::to($to)->queue(new ProfileUpdateHrMail($employee, 'document', [], null, $added));
                } catch (\Throwable $e) {
                    Log::error('Document upload HR email failed: ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', count($added) . ' document(s) uploaded. HR can see them on your record.');
    }

    public function download(Request $request, int $index)
    {
        $doc = $request->user()->employee->documents->firstWhere('index', $index);
        abort_unless($doc && $doc->path && Storage::disk('public')->exists($doc->path), 404);

        return Storage::disk('public')->download($doc->path, Str::slug(pathinfo($doc->name, PATHINFO_FILENAME)) . '.' . $doc->ext);
    }

    public function destroy(Request $request, int $index)
    {
        $employee = $request->user()->employee;
        $doc = $employee->documents->firstWhere('index', $index);

        abort_unless($doc, 404);
        if ($doc->uploaded_by !== 'employee' || ! config('portal.employees_can_delete_own_documents', true)) {
            return back()->with('error', 'Documents added by HR can only be removed by HR.');
        }

        $employee->detachDocument($index);

        return back()->with('success', "“{$doc->name}” deleted.");
    }
}
