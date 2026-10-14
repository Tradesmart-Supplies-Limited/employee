<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Announcements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    public function __construct(private Announcements $svc) {}

    public function index(Request $request)
    {
        $e = $request->user()->employee;
        $list = Announcement::visibleTo($e);
        $cat = $request->query('category');

        return view('portal.announcements.index', [
            'list'    => $cat ? $list->where('category', $cat)->values() : $list,
            'all'     => $list,
            'read'    => array_flip($this->svc->readIds($e)),
            'cat'     => $cat,
        ]);
    }

    public function show(Request $request, Announcement $announcement)
    {
        $e = $request->user()->employee;
        abort_unless($announcement->is_live && $announcement->isFor($e), 404);
        $this->svc->markRead($announcement, $e);

        return view('portal.announcements.show', [
            'a'     => $announcement->load('author'),
            'more'  => Announcement::visibleTo($e)->where('id', '!=', $announcement->id)->take(4),
            'read'  => array_flip($this->svc->readIds($e)),
        ]);
    }

    public function attachment(Request $request, Announcement $announcement)
    {
        $e = $request->user()->employee;
        abort_unless($announcement->is_live && $announcement->isFor($e) && $announcement->attachment_path && Storage::disk('local')->exists($announcement->attachment_path), 404);

        return Storage::disk('local')->download($announcement->attachment_path, $announcement->attachment_name);
    }

    /** "Mark all as read" from the list. */
    public function readAll(Request $request)
    {
        $e = $request->user()->employee;
        Announcement::visibleTo($e)->each(fn ($a) => $this->svc->markRead($a, $e));

        return back()->with('success', 'All announcements marked as read.');
    }
}
