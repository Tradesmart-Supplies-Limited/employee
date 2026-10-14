<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Forgot / reset password for every account (admin, HR and employees).
 *
 *   method=link → emailed link  /reset-password/{token}   (Laravel password broker, 60 min)
 *   method=otp  → emailed 6-digit code, entered on /forgot-password/code (10 min, 5 tries)
 *
 * Responses never reveal whether an email address has an account.
 */
class PasswordResetController extends Controller
{
    private const OTP_MINUTES  = 10;
    private const OTP_ATTEMPTS = 5;

    private const GENERIC = 'If an account exists for that email, we have sent it password reset instructions. Please check your inbox (and spam folder).';

    /* ---------------- Step 1: ask for the email ---------------- */

    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email'  => 'required|email',
            'method' => 'required|in:link,otp',
        ]);

        $email = strtolower(trim($data['email']));
        $key   = 'password-reset:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $mins = (int) ceil(RateLimiter::availableIn($key) / 60);

            return back()->withInput()->withErrors(['email' => "Too many requests. Please try again in {$mins} minute(s)."]);
        }
        RateLimiter::hit($key, 15 * 60);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($data['method'] === 'link') {
            if ($user) {
                $token = Password::broker()->createToken($user);
                $url   = route('password.reset', ['token' => $token, 'email' => $user->email]);
                $this->send($user, new PasswordResetMail($user, url: $url, minutes: (int) config('auth.passwords.users.expire', 60)));
            }

            return back()->with('status', self::GENERIC);
        }

        // OTP
        if ($user) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_otps')->where('email', $email)->delete();
            DB::table('password_reset_otps')->insert([
                'email'      => $email,
                'code_hash'  => Hash::make($code),
                'attempts'   => 0,
                'expires_at' => now()->addMinutes(self::OTP_MINUTES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->send($user, new PasswordResetMail($user, code: $code, minutes: self::OTP_MINUTES));
        }

        return redirect()
            ->route('password.otp', ['email' => $email])
            ->with('status', 'If an account exists for that email, a 6-digit code is on its way. It expires in ' . self::OTP_MINUTES . ' minutes.');
    }

    /* ---------------- Link flow ---------------- */

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request) {
                $this->applyNewPassword($user, $password, $request);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This reset link is invalid or has expired. Please request a new one.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Your password has been reset. You can sign in now.');
    }

    /* ---------------- OTP flow ---------------- */

    public function otpForm(Request $request)
    {
        return view('auth.verify-otp', [
            'email'   => $request->query('email', old('email')),
            'minutes' => self::OTP_MINUTES,
        ]);
    }

    public function otpVerify(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'code'     => 'required|digits:6',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $email  = strtolower(trim($data['email']));
        $record = DB::table('password_reset_otps')->where('email', $email)->latest('id')->first();
        $fail   = fn (string $msg) => back()->withInput($request->only('email'))->withErrors(['code' => $msg]);

        if (! $record || now()->greaterThan($record->expires_at)) {
            return $fail('This code has expired or is not valid. Please request a new one.');
        }

        if ($record->attempts >= self::OTP_ATTEMPTS) {
            DB::table('password_reset_otps')->where('id', $record->id)->delete();

            return $fail('Too many wrong attempts. Please request a new code.');
        }

        if (! Hash::check($data['code'], $record->code_hash)) {
            DB::table('password_reset_otps')->where('id', $record->id)->increment('attempts');
            $left = self::OTP_ATTEMPTS - $record->attempts - 1;

            return $fail("That code is incorrect. {$left} attempt(s) left.");
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return $fail('This code has expired or is not valid. Please request a new one.');
        }

        DB::table('password_reset_otps')->where('email', $email)->delete();
        Password::broker()->deleteToken($user);

        $this->applyNewPassword($user, $data['password'], $request);

        return redirect()->route('login')->with('success', 'Your password has been reset. You can sign in now.');
    }

    /* ---------------- helpers ---------------- */

    private function applyNewPassword(User $user, string $password, Request $request): void
    {
        $user->forceFill([
            'password'             => $password, // hashed by the model cast
            'must_change_password' => false,
            'remember_token'       => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $this->send($user, new PasswordChangedMail($user, (string) $request->ip()));
    }

    private function send(User $user, $mailable): void
    {
        try {
            Mail::to($user->email)->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Password reset email failed for ' . $user->email, ['error' => $e->getMessage()]);
        }
    }
}
