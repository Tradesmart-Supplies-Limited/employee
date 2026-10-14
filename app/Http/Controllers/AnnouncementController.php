<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Services\Announcements;
use App\Services\DepartmentStats;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function __construct(private Announcements $svc) {}

    public function index(Request $request)
    {
        $state = $request->query('state');
        $list = Announcement::with('author')->withCount('reads')
            ->orderByDesc('pinned')->latest()->get()
            ->filter(fn ($a) => ! $state || $a->state === $state)->values();

        return view('dashboard.announcements.index', ['list' => $list, 'state' => $state]);
    }

    public function create(Request $request)
    {
        $a = new Announcement(['category' => 'general', 'priority' => 'normal', 'send_email' => true]);
        if ($d = $request->query('department')) {
            $a->departments = [$d];
        }

        return view('dashboard.announcements.form', ['a' => $a, 'names' => DepartmentStats::names()]);
    }

    public function store(Request $request)
    {
        $a = new Announcement(['created_by' => $request->user()?->id]);
        $this->save($request, $a);

        $sent = $this->svc->emailDue();

        return redirect()->route('announcements.show', $a)->with('success', $a->state === 'scheduled'
            ? 'Announcement scheduled for ' . $a->publish_at->format('D d M, H:i') . '.' . ($a->send_email ? ' It will be emailed then.' : '')
            : 'Announcement published to ' . $a->audience_label . '.' . ($sent ? " Emailing {$sent} employee(s)." : ''));
    }

    public function show(Announcement $announcement)
    {
        $audience = $announcement->audience();
        $reads = AnnouncementRead::where('announcement_id', $announcement->id)->get()->keyBy('employee_id');

        return view('dashboard.announcements.show', [
            'a'        => $announcement->load('author'),
            'audience' => $audience->sortBy(fn ($e) => [isset($reads[$e->id]) ? 1 : 0, $e->first_name]),
            'reads'    => $reads,
        ]);
    }

    public function edit(Announcement $announcement)
    {
        return view('dashboard.announcements.form', ['a' => $announcement, 'names' => DepartmentStats::names()]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->save($request, $announcement);
        $this->svc->emailDue();

        return redirect()->route('announcements.show', $announcement)->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->attachment_path) {
            Storage::disk('local')->delete($announcement->attachment_path);
        }
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted. Employees no longer see it.');
    }

    public function attachment(Announcement $announcement)
    {
        abort_unless($announcement->attachment_path && Storage::disk('local')->exists($announcement->attachment_path), 404);

        return Storage::disk('local')->download($announcement->attachment_path, $announcement->attachment_name);
    }

    private function save(Request $request, Announcement $a): void
    {
        $names = DepartmentStats::names();
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:150'],
            'body'          => ['required', 'string', 'max:10000'],
            'category'      => ['required', 'in:' . implode(',', array_keys(Announcement::CATEGORIES))],
            'priority'      => ['required', 'in:' . implode(',', array_keys(Announcement::PRIORITIES))],
            'audience'      => ['required', 'in:all,departments'],
            'departments'   => ['required_if:audience,departments', 'array'],
            'departments.*' => ['string', \Illuminate\Validation\Rule::in($names)],
            'publish_at'    => ['nullable', 'date'],
            'expires_at'    => ['nullable', 'date', 'after:now'],
            'attachment'    => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,ppt,pptx'],
        ], [
            'departments.required_if' => 'Pick at least one department, or send it to everyone.',
            'expires_at.after'        => 'The "show until" date must be in the future.',
        ]);

        $tz = \App\Services\AttendanceService::tz();   // the form shows Lusaka time
        $a->fill([
            'title'       => $data['title'],
            'body'        => $data['body'],
            'category'    => $data['category'],
            'priority'    => $data['priority'],
            'departments' => $data['audience'] === 'departments' ? array_values(array_unique($data['departments'] ?? [])) : null,
            'pinned'      => $request->boolean('pinned'),
            'publish_at'  => ! empty($data['publish_at']) ? Carbon::parse($data['publish_at'], $tz)->utc() : null,
            'expires_at'  => ! empty($data['expires_at']) ? Carbon::parse($data['expires_at'], $tz)->utc() : null,
            'send_email'  => $request->boolean('send_email'),
        ]);

        if ($request->boolean('remove_attachment') && $a->attachment_path) {
            Storage::disk('local')->delete($a->attachment_path);
            $a->attachment_path = $a->attachment_name = null;
        }
        if ($file = $request->file('attachment')) {
            if ($a->attachment_path) {
                Storage::disk('local')->delete($a->attachment_path);
            }
            $a->attachment_path = $file->store('announcements', 'local');
            $a->attachment_name = $file->getClientOriginalName();
        }
        $a->save();
    }
}
