<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SetupVerifyMail;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * First-time setup: creates the very first (Admin) login, step by step.
 *
 *   1. details  – name + email
 *   2. confirm  – a 6-digit code is emailed; entering it proves the address works
 *   3. password – choose a password
 *   4. done     – account created with a verified email and signed in
 *
 * Only open while the system has no logins at all. Progress lives in the
 * session under "setup"; the code is stored hashed, expires and allows 5 tries.
 */
class SetupController extends Controller
{
    private const CODE_MINUTES = 15;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_SECONDS = 60;

    /** The current step. */
    public function show(Request $request)
    {
        if ($closed = $this->closed()) {
            return $closed;
        }

        $s = $request->session()->get('setup', []);
        $step = match (true) {
            ! empty($s['verified']) => 'password',
            ! empty($s['email'])    => 'confirm',
            default                 => 'details',
        };

        return view('auth.setup', [
            'step'        => $step,
            'name'        => $s['name'] ?? old('name'),
            'email'       => $s['email'] ?? old('email'),
            'minutes'     => self::CODE_MINUTES,
            'resendIn'    => isset($s['sent_at']) ? max(0, self::RESEND_SECONDS - (time() - $s['sent_at'])) : 0,
            'expired'     => isset($s['expires_at']) && time() > $s['expires_at'],
            'triesLeft'   => self::MAX_ATTEMPTS - ($s['attempts'] ?? 0),
        ]);
    }

    /** Step 1 → 2: save name + email and email the code. */
    public function details(Request $request)
    {
        if ($closed = $this->closed()) {
            return $closed;
        }

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'name.required'  => 'Add your name so it can go on your account.',
            'email.required' => 'Add the email address you want to sign in with.',
            'email.email'    => 'That doesn\'t look like an email address. Check for typos.',
        ]);

        $request->session()->put('setup', [
            'name'  => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
        ]);

        if (! $this->sendCode($request)) {
            return redirect()->route('setup')->with('error', 'We couldn\'t send the email. Check the MAIL_ settings in your .env file, then use "Send a new code".');
        }

        return redirect()->route('setup')->with('status', 'Code sent. Check your inbox.');
    }

    /** Send a new code (after the cool-down). */
    public function resend(Request $request)
    {
        if ($closed = $this->closed()) {
            return $closed;
        }
        $s = $request->session()->get('setup', []);
        if (empty($s['email'])) {
            return redirect()->route('setup');
        }
        if (isset($s['sent_at']) && time() - $s['sent_at'] < self::RESEND_SECONDS) {
            return redirect()->route('setup')->with('error', 'Please wait a minute before asking for another code.');
        }

        return redirect()->route('setup')->with(...($this->sendCode($request)
            ? ['status', 'New code sent to ' . $s['email'] . '.']
            : ['error', 'We couldn\'t send the email. Check the MAIL_ settings in your .env file.']));
    }

    /** Step 2 → 3: check the code. */
    public function verify(Request $request)
    {
        if ($closed = $this->closed()) {
            return $closed;
        }
        $s = $request->session()->get('setup', []);
        if (empty($s['code_hash'])) {
            return redirect()->route('setup');
        }

        $code = preg_replace('/\D/', '', (string) $request->input('code'));

        if (time() > ($s['expires_at'] ?? 0)) {
            return redirect()->route('setup')->withErrors(['code' => 'That code has expired. Send a new one.']);
        }
        if (($s['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            return redirect()->route('setup')->withErrors(['code' => 'Too many wrong codes. Send a new one.']);
        }

        if (strlen($code) !== 6 || ! Hash::check($code, $s['code_hash'])) {
            $s['attempts'] = ($s['attempts'] ?? 0) + 1;
            $request->session()->put('setup', $s);
            $left = self::MAX_ATTEMPTS - $s['attempts'];

            return redirect()->route('setup')->withErrors(['code' => $left > 0
                ? "That code doesn't match. {$left} " . ($left === 1 ? 'try' : 'tries') . ' left.'
                : 'Too many wrong codes. Send a new one.']);
        }

        $s['verified'] = true;
        unset($s['code_hash']);
        $request->session()->put('setup', $s);

        return redirect()->route('setup')->with('status', 'Email confirmed.');
    }

    /** Step 3 → done: choose a password, create the Admin and sign in. */
    public function password(Request $request)
    {
        if ($closed = $this->closed()) {
            return $closed;
        }
        $s = $request->session()->get('setup', []);
        if (empty($s['verified']) || empty($s['email'])) {
            return redirect()->route('setup');
        }

        $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The two passwords don\'t match.',
        ]);

        $user = DB::transaction(function () use ($s, $request) {
            // Someone may have finished setup in another tab.
            if (User::lockForUpdate()->exists()) {
                return null;
            }

            $user = User::create([
                'name'     => $s['name'],
                'email'    => $s['email'],
                'password' => $request->input('password'),
                'role'     => Roles::ADMIN,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });

        $request->session()->forget('setup');

        if (! $user) {
            return redirect()->route('login')->with('error', 'Setup was already finished. Sign in instead.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('setup_complete', true);

        return redirect()->route('setup.done');
    }

    /** Change the email address (back to step 1). */
    public function restart(Request $request)
    {
        $name = $request->session()->get('setup.name');
        $request->session()->forget('setup');

        return redirect()->route('setup')->withInput(['name' => $name]);
    }

    /** The badge is issued: shown once, right after setup. */
    public function done(Request $request)
    {
        if (! $request->session()->pull('setup_complete')) {
            return redirect()->route('dashboard');
        }

        return view('auth.setup', ['step' => 'done', 'name' => $request->user()->name, 'email' => $request->user()->email]);
    }

    // ------------------------------------------------------------------

    /** Setup is closed as soon as any login exists. */
    private function closed()
    {
        return User::exists()
            ? redirect()->route('login')->with('status', 'The system is already set up. Sign in, or ask HR for a login.')
            : null;
    }

    private function sendCode(Request $request): bool
    {
        $s = $request->session()->get('setup');
        $key = 'setup-mail:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return false;
        }
        RateLimiter::hit($key, 15 * 60);

        $code = (string) random_int(100000, 999999);
        $s['code_hash'] = Hash::make($code);
        $s['expires_at'] = time() + self::CODE_MINUTES * 60;
        $s['attempts'] = 0;
        $s['sent_at'] = time();
        $s['verified'] = false;
        $request->session()->put('setup', $s);

        try {
            // Sent straight away (never queued) so the code isn't stored in the jobs table.
            Mail::to($s['email'])->send(new SetupVerifyMail($s['name'], $s['email'], $code, self::CODE_MINUTES));

            return true;
        } catch (\Throwable $e) {
            Log::error('Setup: verification email failed for ' . $s['email'], ['error' => $e->getMessage()]);
            if (app()->environment('local')) {
                // Local development only: lets you finish setup before mail is configured.
                Log::warning("Setup code for {$s['email']}: {$code} (logged because APP_ENV=local and the email could not be sent)");
            }

            return false;
        }
    }
}
