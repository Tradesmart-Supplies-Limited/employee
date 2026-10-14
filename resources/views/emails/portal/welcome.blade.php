@extends('emails.layouts.brand')

@section('title', 'Employee Self-Service portal')
@section('tag', 'Employee Self-Service')
@section('preheader', $isReset ? 'Your new temporary password is inside.' : 'Your login details and a quick guide to the portal.')

@section('heading')
    @if($isReset)
        Your portal password has been reset
    @else
        Welcome aboard, {{ $employee->first_name }}! &#128075;
    @endif
@endsection

@section('body')
    <p style="margin:0 0 12px;">Hi {{ $employee->first_name }},</p>

    @if($isReset)
        <p style="margin:0 0 16px;">HR has issued you a new temporary password for the Employee Self-Service portal. Your old password no longer works.</p>
    @else
        <p style="margin:0 0 16px;">
            Your <strong>Employee Self-Service portal</strong> account is ready. Use it to check your details,
            apply for leave and follow it through approval, request overtime and download your payslips, any time and from any device.
        </p>
    @endif

    {{-- CREDENTIALS --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f6fd; border:1px solid #cfe0f5; border-radius:10px;">
        <tr><td style="padding:16px 18px 6px; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#185FA5;">Your login details</td></tr>
        <tr><td style="padding:4px 18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                <tr>
                    <td style="padding:6px 0; color:#64748b; width:150px;">Portal address</td>
                    <td style="padding:6px 0;"><a href="{{ $loginUrl }}" style="color:#185FA5; font-weight:600;">{{ $loginUrl }}</a></td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#64748b;">Email (username)</td>
                    <td style="padding:6px 0; font-weight:600; color:#0f172a;">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#64748b;">Temporary password</td>
                    <td style="padding:6px 0;">
                        <span style="font-family:Consolas, 'Courier New', monospace; font-size:16px; font-weight:700; letter-spacing:.06em; background:#ffffff; border:1px dashed #94b8e0; border-radius:6px; padding:4px 10px; color:#0f172a;">{{ $tempPassword }}</span>
                    </td>
                </tr>
            </table>
        </td></tr>
        <tr><td style="padding:8px 18px 16px; font-size:12px; color:#b45309;">
            &#9888; You'll be asked to choose your own password the first time you sign in. Don't share this password with anyone. HR will never ask you for it.
        </td></tr>
    </table>

    @include('emails.layouts.partials.button', ['url' => $loginUrl, 'label' => 'Sign in to the portal'])

    @unless($isReset)
        {{-- QUICK START --}}
        <h2 style="font-size:16px; margin:8px 0 10px; color:#0f172a;">Getting started in 5 steps</h2>
        @php
            $steps = [
                ['Sign in & set your password', 'Open the portal address above, sign in with your email and the temporary password, then choose a new password (at least 8 characters, with letters and numbers).'],
                ['Check “My Information”', 'Make sure your contact details, emergency contact and next of kin are correct. You can update some of them yourself; ask HR to change the rest.'],
                ['Apply for leave', 'Go to <strong>My Leave → Apply for Leave</strong>, pick your dates and leave type. Sundays and Zambian public holidays aren\'t counted, and your return date is worked out for you.' . ($supervisor ? ' Your request goes to <strong>' . e($supervisor) . '</strong> first, then to HR.' : ' HR reviews your request.')],
                ['Track every request', 'Each request shows a progress tracker (Submitted → Supervisor → HR → Balance updated), and you get an email at every step. Your leave balance is always on your dashboard.'],
                ['Overtime & payslips', 'Request overtime under <strong>My Overtime</strong>, and view or download your payslips under <strong>My Payslips</strong> once payroll is finalised.'],
            ];
        @endphp
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            @foreach($steps as $i => [$title, $text])
                <tr>
                    <td valign="top" style="width:38px; padding:0 0 14px;">
                        <div style="width:28px; height:28px; border-radius:14px; background:#185FA5; color:#fff; font-weight:700; font-size:13px; text-align:center; line-height:28px;">{{ $i + 1 }}</div>
                    </td>
                    <td valign="top" style="padding:3px 0 14px; font-size:13.5px; line-height:1.55;">
                        <strong style="color:#0f172a;">{{ $title }}</strong><br>
                        <span style="color:#475569;">{!! $text !!}</span>
                    </td>
                </tr>
            @endforeach
        </table>
    @endunless

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; margin-top:6px;">
        <tr><td style="padding:12px 16px; font-size:13px; color:#475569; line-height:1.6;">
            <strong style="color:#0f172a;">Forgot your password later?</strong>
            Click “Forgot Password?” on the sign-in page (<a href="{{ $forgotUrl }}" style="color:#185FA5;">{{ $forgotUrl }}</a>)
            and choose to receive a reset link or a one-time code by email.
        </td></tr>
    </table>
@endsection

@section('footnote')
    Questions or trouble signing in? Contact HR{{ $hrContact ? ' at ' . $hrContact : '' }}.
    If you weren't expecting this email, please tell HR straight away.
@endsection
