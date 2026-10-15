<?php

namespace App\Http\Controllers;

use App\Mail\PortalWelcomeMail;
use App\Mail\StaffLoginMail;
use App\Models\Employee;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Users & Roles: who can sign in and what they can do.
 *
 * Admin, HR and Director can manage logins. Only an Admin can create, change
 * or remove an Admin login, nobody can change their own role, and the last
 * Admin can't be removed, so the system can never be locked out.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = in_array($request->query('role'), Roles::ALL, true) ? $request->query('role') : null;
        $q = trim((string) $request->query('q'));

        $users = User::with('employee')
            ->when($role, fn ($w) => $w->whereRaw('LOWER(role) = ?', [$role]))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
                ->orWhereHas('employee', fn ($e) => $e->where('department', 'like', "%{$q}%")->orWhere('employee_id', 'like', "%{$q}%"))))
            ->orderByRaw("CASE LOWER(role) WHEN 'admin' THEN 0 WHEN 'hr' THEN 1 WHEN 'director' THEN 2 WHEN 'manager' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate(30)->withQueryString();

        $counts = User::selectRaw('LOWER(role) as r, COUNT(*) as n')->groupBy('r')->pluck('n', 'r');

        $linked = User::whereNotNull('employee_id')->pluck('employee_id')->all();
        $employees = Employee::orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'employee_id', 'first_name', 'last_name', 'department', 'position', 'company_email', 'personal_email', 'employment_status']);

        return view('dashboard.users.index', [
            'users'     => $users,
            'counts'    => $counts,
            'total'     => $counts->sum(),
            'role'      => $role,
            'q'         => $q,
            'roles'     => Roles::options(),
            'employees' => $employees,
            'linked'    => $linked,
            'me'        => $request->user(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'        => ['required', Rule::in(Roles::ALL)],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', 'unique:users,employee_id'],
            'password'    => ['nullable', 'string', 'min:8', 'max:100'],
        ], [
            'employee_id.unique' => 'That employee already has a login. Change its role instead.',
        ]);

        $this->guardAdminRole($request->user(), $data['role']);
        if ($data['role'] === Roles::EMPLOYEE && empty($data['employee_id'])) {
            return back()->withInput()->withErrors(['employee_id' => 'An Employee login must be linked to an employee record (it is their self-service portal).']);
        }

        $generated = blank($data['password'] ?? null);
        $password = $generated ? $this->tempPassword() : $data['password'];

        $user = User::create([
            'name'                 => $data['name'],
            'email'                => strtolower($data['email']),
            'password'             => $password,
            'role'                 => $data['role'],
            'employee_id'          => $data['employee_id'] ?? null,
            // A generated password is temporary: they choose their own at first sign-in.
            'must_change_password' => $generated,
        ]);

        $emailed = $this->sendLoginEmail($user, $generated ? $password : null, $request->user()->name);

        $msg = "{$user->name} can now sign in as " . Roles::label($user->role) . '.';
        if ($emailed) {
            $msg .= ' Their login details were emailed to ' . $user->email . '.';
        } elseif ($generated) {
            $msg .= ' The email could not be sent (check the MAIL_ settings). Temporary password, shown once, pass it on securely: ' . $password;
        } else {
            $msg .= ' The email could not be sent (check the MAIL_ settings). Give them the password you set.';
        }

        return redirect()->route('users.index')->with($emailed ? 'success' : 'error', $msg);
    }

    /** Issue a new temporary password and email it (also fixes "they never got the email"). */
    public function resetPassword(Request $request, User $user)
    {
        $me = $request->user();
        $this->guardAdminRole($me, $user->roleKey());
        if ($user->is($me)) {
            return back()->with('error', 'Use "Forgot password" on the sign-in page to change your own password.');
        }

        $password = $this->tempPassword();
        $user->update(['password' => $password, 'must_change_password' => true]);

        if ($this->sendLoginEmail($user, $password, $me->name, true)) {
            return redirect()->route('users.index', $request->only('q', 'role'))->with('success', "New login details emailed to {$user->email}. Their old password no longer works.");
        }

        return redirect()->route('users.index', $request->only('q', 'role'))
            ->with('error', "The email to {$user->email} could not be sent (check the MAIL_ settings). Temporary password, shown once, pass it on securely: {$password}");
    }

    public function update(Request $request, User $user)
    {
        $me = $request->user();
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'        => ['required', Rule::in(Roles::ALL)],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id', Rule::unique('users', 'employee_id')->ignore($user->id)],
        ], [
            'employee_id.unique' => 'That employee is already linked to another login.',
        ]);

        $newRole = $data['role'];
        $oldRole = $user->roleKey();

        if ($user->is($me) && $newRole !== $oldRole) {
            return back()->with('error', 'You can\'t change your own role. Ask another Admin.');
        }
        $this->guardAdminRole($me, $oldRole);
        $this->guardAdminRole($me, $newRole);
        if ($oldRole === Roles::ADMIN && $newRole !== Roles::ADMIN && $this->adminCount() <= 1) {
            return back()->with('error', 'This is the only Admin. Make someone else an Admin first.');
        }
        if ($newRole === Roles::EMPLOYEE && empty($data['employee_id'])) {
            return back()->with('error', 'An Employee login must be linked to an employee record.');
        }

        $user->update([
            'name'        => $data['name'],
            'email'       => strtolower($data['email']),
            'role'        => $newRole,
            'employee_id' => $data['employee_id'] ?? null,
        ]);

        return redirect()->route('users.index', $request->only('q', 'role'))
            ->with('success', "{$user->name} is now " . Roles::label($newRole) . '.' . ($newRole === Roles::DIRECTOR && $user->employee_id ? ' They are left out of attendance, rotas and leave reports from now on.' : ''));
    }

    public function destroy(Request $request, User $user)
    {
        $me = $request->user();
        if ($user->is($me)) {
            return back()->with('error', 'You can\'t remove your own login.');
        }
        $this->guardAdminRole($me, $user->roleKey());
        if ($user->roleKey() === Roles::ADMIN && $this->adminCount() <= 1) {
            return back()->with('error', 'This is the only Admin and can\'t be removed.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "{$name}'s login was removed. Their employee record is unchanged.");
    }

    /**
     * Employee logins get the portal welcome; everyone else gets an email
     * that explains their role. Sent straight away (it may hold a password).
     */
    private function sendLoginEmail(User $user, ?string $tempPassword, string $by, bool $isReset = false): bool
    {
        try {
            if ($user->roleKey() === Roles::EMPLOYEE && $user->employee && $tempPassword) {
                Mail::to($user->email)->send(new PortalWelcomeMail($user, $user->employee, $tempPassword, $isReset));
            } else {
                Mail::to($user->email)->send(new StaffLoginMail($user, $tempPassword, $by, $isReset));
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Users: login email failed for ' . $user->email, ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** Only Admins may grant, change or remove the Admin role. */
    private function guardAdminRole(User $me, string $role): void
    {
        abort_if($role === Roles::ADMIN && $me->roleKey() !== Roles::ADMIN, 403, 'Only an Admin can manage Admin logins.');
    }

    private function adminCount(): int
    {
        return User::whereRaw('LOWER(role) = ?', [Roles::ADMIN])->count();
    }

    private function tempPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $part = fn () => collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');

        return $part() . '-' . $part() . '-' . random_int(10, 99);
    }
}
