@extends('emails.layouts.brand')

@section('title', 'Your HR system login')
@section('tag', $role . ' login')
@section('preheader', $isReset ? 'Your new temporary password is inside.' : "You can now sign in to the HR system as {$role}.")

@section('heading')
    @if($isReset)
        Your HR system password has been reset
    @else
        You now have {{ in_array($roleKey, ['admin', 'employee']) ? 'an' : 'a' }} {{ $role }} login, {{ explode(' ', trim($user->name))[0] }}
    @endif
@endsection

@section('body')
    <p style="margin:0 0 12px;">Hi {{ explode(' ', trim($user->name))[0] }},</p>

    @if($isReset)
        <p style="margin:0 0 16px;">{{ $createdBy }} issued you a new temporary password for the HR system. Your old password no longer works.</p>
    @else
        <p style="margin:0 0 16px;">{{ $createdBy }} has given you <strong>{{ $role }}</strong> access to the {{ config('company.name') }} HR system.</p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f6fd; border:1px solid #cfe0f5; border-radius:10px;">
        <tr><td style="padding:16px 18px 6px; font-size:13px; font-weight:700; color:#185FA5;">Your login details</td></tr>
        <tr><td style="padding:4px 18px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
                <tr>
                    <td style="padding:6px 0; color:#64748b; width:150px;">Sign-in page</td>
                    <td style="padding:6px 0;"><a href="{{ $loginUrl }}" style="color:#185FA5; font-weight:600; word-break:break-all;">{{ $loginUrl }}</a></td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#64748b;">Email (username)</td>
                    <td style="padding:6px 0; font-weight:600; color:#0f172a;">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#64748b;">Role</td>
                    <td style="padding:6px 0;"><span style="display:inline-block; background:{{ $color }}; color:#ffffff; font-weight:700; font-size:12px; border-radius:999px; padding:3px 10px;">{{ $role }}</span></td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#64748b;">Password</td>
                    <td style="padding:6px 0;">
                        @if($tempPassword)
                            <span style="font-family:Consolas, 'Courier New', monospace; font-size:16px; font-weight:700; letter-spacing:.06em; background:#ffffff; border:1px dashed #94b8e0; border-radius:6px; padding:4px 10px; color:#0f172a;">{{ $tempPassword }}</span>
                        @else
                            <span style="color:#0f172a;">Set by {{ $createdBy }}, who will give it to you. Or choose your own with <a href="{{ $forgotUrl }}" style="color:#185FA5;">Forgot password</a>.</span>
                        @endif
                    </td>
                </tr>
            </table>
        </td></tr>
        @if($tempPassword)
            <tr><td style="padding:8px 18px 16px; font-size:12px; color:#b45309;">
                &#9888; This is a temporary password. You'll be asked to choose your own the first time you sign in. Don't share it with anyone.
            </td></tr>
        @else
            <tr><td style="padding:0 0 10px;"></td></tr>
        @endif
    </table>

    @include('emails.layouts.partials.button', ['url' => $loginUrl, 'label' => 'Sign in as ' . $role])

    @if($guide)
        <h2 style="font-size:16px; margin:8px 0 10px; color:#0f172a;">What your {{ $role }} login opens</h2>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13.5px; line-height:1.55;">
            @foreach($guide['can'] as $c)
                <tr>
                    <td valign="top" style="width:24px; padding:3px 0; color:{{ $color }}; font-weight:800;">&#10003;</td>
                    <td style="padding:3px 0; color:#334155;">{{ $c }}</td>
                </tr>
            @endforeach
        </table>
        <p style="margin:12px 0 0; font-size:13.5px; color:#475569;">After signing in you'll land on <strong>{{ $guide['lands'] }}</strong>.@if($portal) You can also switch to your own self-service portal from the menu.@endif</p>
    @endif
@endsection

@section('footnote')
    Weren't expecting this? @if($hrContact)Let HR know at {{ $hrContact }}.@else Let HR know.@endif Never share your password; HR will never ask you for it.
@endsection
