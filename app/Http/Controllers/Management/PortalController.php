<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ManagementAttendance;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Management self-service portal: their own profile and photo, and their own
 * clock-in / clock-out (kept apart from staff attendance and its reports).
 */
class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        ManagementAttendance::closeOpenDays($user->id);
        $today = AttendanceService::today();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);

        $week = ManagementAttendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $weekStart->toDateString())->whereDate('date', '<=', $weekStart->copy()->addDays(6)->toDateString())
            ->get()->keyBy(fn ($r) => $r->date->format('Y-m-d'));
        $month = ManagementAttendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $today->copy()->startOfMonth()->toDateString())->whereDate('date', '<=', $today->toDateString())
            ->get();

        return view('management.dashboard', [
            'user'       => $user,
            'todayRec'   => $week[$today->toDateString()] ?? null,
            'weekDays'   => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i)),
            'week'       => $week,
            'monthDays'  => $month->whereNotNull('clock_in')->count(),
            'monthMins'  => $month->sum(fn ($r) => (int) $r->minutes),
            'weekMins'   => $week->sum(fn ($r) => (int) $r->minutes),
            'today'      => $today,
            'now'        => AttendanceService::now(),
            'completion' => $this->completion($user),
        ]);
    }

    // ------------------------------------------------------------ profile

    public function profile(Request $request)
    {
        return view('management.profile', ['user' => $request->user(), 'completion' => $this->completion($request->user())]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:120'],
            'email'            => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'job_title'        => ['nullable', 'string', 'max:120'],
            'office_location'  => ['nullable', 'string', 'max:120'],
            'current_password' => ['nullable', 'string'],
        ], [
            'email.unique' => 'That email address is already used by another login. Please use a different one.',
        ]);

        // Changing the sign-in email needs the current password.
        if (strtolower($data['email']) !== strtolower($user->email)
            && ! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
            return back()->withInput()->withErrors(['current_password' => 'To change your sign-in email, please confirm your current password.']);
        }

        $user->update(collect($data)->except('current_password')->all());

        return back()->with('success', 'Thank you, your profile has been updated.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'photo.required' => 'Please choose a photo to upload.',
            'photo.image'    => 'Please choose an image file (JPG, PNG or WebP).',
            'photo.max'      => 'Please choose a photo smaller than 4 MB.',
        ]);

        $user = $request->user();
        $old = $user->photo_path;
        $path = $request->file('photo')->store('profile-photos', 'public');
        $user->update(['photo_path' => $path]);
        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Thank you, your new profile photo is saved.');
    }

    public function removePhoto(Request $request)
    {
        $user = $request->user();
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->update(['photo_path' => null]);
        }

        return back()->with('success', 'Your profile photo has been removed.');
    }

    // ------------------------------------------------------------ attendance

    public function attendance(Request $request)
    {
        $user = $request->user();
        ManagementAttendance::closeOpenDays($user->id);
        $month = $request->filled('month') && preg_match('/^\d{4}-\d{2}$/', $request->month)
            ? Carbon::createFromFormat('Y-m-d', $request->month . '-01', AttendanceService::tz())->startOfDay()
            : AttendanceService::today()->startOfMonth();
        $today = AttendanceService::today();

        $rows = ManagementAttendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $month->toDateString())->whereDate('date', '<=', $month->copy()->endOfMonth()->toDateString())
            ->get()->keyBy(fn ($r) => $r->date->format('Y-m-d'));

        $days = collect();
        for ($d = $month->copy(); $d->lte($month->copy()->endOfMonth()) && $d->lte($today); $d->addDay()) {
            $days->push($d->copy());
        }

        return view('management.attendance', [
            'user'     => $user,
            'month'    => $month,
            'days'     => $days->reverse()->values(),
            'rows'     => $rows,
            'total'    => $rows->sum(fn ($r) => (int) $r->minutes),
            'present'  => $rows->whereNotNull('clock_in')->count(),
            'todayRec' => ManagementAttendance::where('user_id', $user->id)->whereDate('date', $today->toDateString())->first(),
            'today'    => $today,
            'canNext'  => $month->copy()->addMonth()->lte($today),
        ]);
    }

    /**
     * One button: clock in if not yet in today, otherwise clock out.
     * An optional time lets them record when they actually started (or
     * finished), e.g. if they forgot to press the button on arrival.
     */
    public function clock(Request $request)
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
            'time' => ['nullable', 'date_format:H:i'],
        ], ['time.date_format' => 'Please enter the time as hours and minutes, e.g. 08:15.']);

        $user = $request->user();
        ManagementAttendance::closeOpenDays($user->id);
        $now = AttendanceService::now();
        $rec = ManagementAttendance::where('user_id', $user->id)->whereDate('date', $now->toDateString())->first()
            ?? new ManagementAttendance(['user_id' => $user->id, 'date' => $now->toDateString()]);

        $manual = $request->filled('time') && $request->time !== $now->format('H:i');
        $at = $manual ? $request->time . ':00' : $now->format('H:i:s');
        if ($manual && $at > $now->format('H:i:s')) {
            return back()->withInput()->withErrors(['time' => 'That time is later than now. Please choose a time that has already passed.']);
        }

        if (! $rec->clock_in) {
            $rec->fill(['clock_in' => $at, 'in_source' => $manual ? 'manual' : 'button', 'in_ip' => $request->ip(), 'note' => $request->input('note')])->save();
            $msg = $manual
                ? 'Thank you, ' . explode(' ', $user->name)[0] . '. Your start time today is recorded as ' . substr($at, 0, 5) . '.'
                : 'Good ' . $this->partOfDay($now) . ', ' . explode(' ', $user->name)[0] . '! You clocked in at ' . $now->format('H:i') . '.';
        } elseif (! $rec->clock_out) {
            if ($at <= $rec->clock_in) {
                return back()->withInput()->withErrors(['time' => 'The finish time needs to be after your start time (' . $rec->in_time . ').']);
            }
            $rec->fill(['clock_out' => $at, 'out_source' => $manual ? 'manual' : 'button', 'out_ip' => $request->ip()])->save();
            $msg = 'Thank you, you clocked out at ' . substr($at, 0, 5) . ' (' . ManagementAttendance::hm($rec->minutes) . ' today). Have a lovely evening!';
        } else {
            return back()->with('success', 'You have already clocked in and out today, so no action is needed. If a time needs correcting, you\'re welcome to edit the day under My Attendance.');
        }

        return back()->with('success', $msg);
    }

    /** Add or correct a day (today or earlier). */
    public function saveDay(Request $request)
    {
        $today = AttendanceService::today();
        $data = $request->validate([
            'date'      => ['required', 'date_format:Y-m-d', 'before_or_equal:' . $today->toDateString()],
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'note'      => ['nullable', 'string', 'max:255'],
        ], [
            'date.before_or_equal'  => 'Days in the future can\'t be recorded yet. Please choose today or an earlier day.',
            'clock_in.required'     => 'Please add the time you started.',
            'clock_in.date_format'  => 'Please enter the start time as hours and minutes, e.g. 08:15.',
            'clock_out.date_format' => 'Please enter the finish time as hours and minutes, e.g. 17:00.',
        ]);

        $data['clock_out'] ??= null;
        $data['note'] ??= null;
        $user = $request->user();
        $isToday = $data['date'] === $today->toDateString();
        $now = AttendanceService::now()->format('H:i');

        if ($data['clock_out'] && $data['clock_out'] <= $data['clock_in']) {
            return back()->withInput()->withErrors(['clock_out' => 'The finish time needs to be after the start time.'])->with('editDay', $data['date']);
        }
        if ($isToday && ($data['clock_in'] > $now || ($data['clock_out'] && $data['clock_out'] > $now))) {
            return back()->withInput()->withErrors(['clock_in' => 'Today\'s times can\'t be later than now (' . $now . ').'])->with('editDay', $data['date']);
        }
        if (! $isToday && ! $data['clock_out']) {
            return back()->withInput()->withErrors(['clock_out' => 'Please add the time you finished on that day.'])->with('editDay', $data['date']);
        }

        $rec = ManagementAttendance::where('user_id', $user->id)->whereDate('date', $data['date'])->first()
            ?? new ManagementAttendance(['user_id' => $user->id, 'date' => $data['date']]);
        $wasNew = ! $rec->exists;
        $in = $data['clock_in'] . ':00';
        $out = $data['clock_out'] ? $data['clock_out'] . ':00' : null;

        $rec->fill([
            'clock_in'   => $in,
            'clock_out'  => $out,
            'in_source'  => $rec->clock_in === $in ? ($rec->in_source ?: 'button') : 'manual',
            'out_source' => $out === null ? null : ($rec->clock_out === $out ? ($rec->out_source ?: 'button') : 'manual'),
            'note'       => $data['note'] ?? null,
            'edited_at'  => now(),
        ])->save();

        $day = \Illuminate\Support\Carbon::parse($data['date'])->format('l d M');

        return redirect()->route('mgmt.attendance', ['month' => substr($data['date'], 0, 7)])
            ->with('success', ($wasNew ? 'Thank you, ' . $day . ' has been added.' : 'Thank you, ' . $day . ' has been updated.'));
    }

    /** Remove a day recorded by mistake. */
    public function deleteDay(Request $request, ManagementAttendance $day)
    {
        abort_unless((int) $day->user_id === (int) $request->user()->id, 404);
        $label = $day->date->format('l d M');
        $month = $day->date->format('Y-m');
        $day->delete();

        return redirect()->route('mgmt.attendance', ['month' => $month])->with('success', $label . ' has been removed from your attendance.');
    }

    /** CSV (opens in Excel) for a month or a chosen date range. */
    public function export(Request $request)
    {
        $tz = AttendanceService::tz();
        $today = AttendanceService::today();
        $request->validate([
            'from'  => ['nullable', 'date_format:Y-m-d'],
            'to'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'month' => ['nullable', 'date_format:Y-m'],
        ], ['to.after_or_equal' => 'The end date needs to be on or after the start date.']);

        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from, $tz);
            $to = Carbon::parse($request->to, $tz);
        } else {
            $from = $request->filled('month') ? Carbon::createFromFormat('Y-m-d', $request->month . '-01', $tz) : $today->copy()->startOfMonth();
            $to = $from->copy()->endOfMonth();
        }
        if ($to->gt($today)) {
            $to = $today->copy();
        }

        $user = $request->user();
        $rows = ManagementAttendance::where('user_id', $user->id)
            ->whereDate('date', '>=', $from->toDateString())->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')->get();

        $source = fn (?string $s) => match ($s) {
            'manual' => 'Entered manually',
            'auto'   => 'Auto clock-out at midnight',
            'button' => 'Clock button',
            default  => '',
        };
        $total = $rows->sum(fn ($r) => (int) $r->minutes);
        $name = 'management-attendance-' . \Illuminate\Support\Str::slug($user->name) . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows, $user, $from, $to, $source, $total) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads accents correctly
            fputcsv($out, ['Management attendance', $user->name, $user->job_title ?: 'Management']);
            fputcsv($out, ['Period', $from->format('d M Y') . ' to ' . $to->format('d M Y')]);
            fputcsv($out, []);
            fputcsv($out, ['Date', 'Day', 'Clock in', 'Clock out', 'Hours', 'Hours (decimal)', 'How clock-in was recorded', 'How clock-out was recorded', 'Edited', 'Note']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->date->format('Y-m-d'), $r->date->format('l'), $r->in_time, $r->out_time ?? '',
                    $r->minutes !== null ? ManagementAttendance::hm($r->minutes) : '',
                    $r->minutes !== null ? round($r->minutes / 60, 2) : '',
                    $source($r->in_source), $source($r->out_source),
                    $r->edited_at ? 'Yes' : '', $r->note,
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Days worked', $rows->whereNotNull('clock_in')->count()]);
            fputcsv($out, ['Total hours', ManagementAttendance::hm($total), round($total / 60, 2)]);
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------ helpers

    /** How complete the profile is (for the gentle reminder card). */
    private function completion($user): array
    {
        $checks = [
            'Profile photo' => (bool) $user->photo_path,
            'Job title'     => filled($user->job_title),
            'Office'        => filled($user->office_location),
        ];

        return ['percent' => (int) round(count(array_filter($checks)) / count($checks) * 100), 'missing' => array_keys(array_filter($checks, fn ($v) => ! $v))];
    }

    private function partOfDay(\Carbon\CarbonInterface $t): string
    {
        return $t->hour < 12 ? 'morning' : ($t->hour < 17 ? 'afternoon' : 'evening');
    }
}
