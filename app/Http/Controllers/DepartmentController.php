<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\DepartmentStats;

class DepartmentController extends Controller
{
    public function __construct(private DepartmentStats $stats) {}

    public function index()
    {
        $departments = $this->stats->all();
        $real = $departments->reject(fn ($d) => $d->name === DepartmentStats::NONE);

        return view('dashboard.departments.index', [
            'departments'   => $departments,
            'totals'        => [
                'departments' => $real->count(),
                'active'      => $departments->sum('active'),
                'largest'     => $real->first(),
                'avg_team'    => $real->count() ? round($real->avg('active'), 1) : 0,
                'unassigned'  => optional($departments->firstWhere('name', DepartmentStats::NONE))->active ?? 0,
                'attended'    => $departments->sum(fn ($d) => $d->attendance['attended']),
                'expected'    => $departments->sum(fn ($d) => $d->attendance['expected']),
                'on_leave'    => $departments->sum('on_leave'),
            ],
            'announcements' => Announcement::with('author')->withCount('reads')->latest()->limit(6)->get(),
        ]);
    }

    public function show(string $slug)
    {
        $dept = $this->stats->find($slug);
        abort_unless($dept, 404);

        $announcements = Announcement::with('author')->withCount('reads')->latest()->get()
            ->filter(fn ($a) => empty($a->departments) || in_array($dept->name, $a->departments, true))->take(8)->values();

        return view('dashboard.departments.show', [
            'd'             => $dept,
            'announcements' => $announcements,
            'others'        => $this->stats->all()->pluck('name', 'slug'),
        ]);
    }
}
