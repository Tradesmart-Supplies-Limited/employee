@extends('emails.layouts.brand')

@section('title', 'Reset your password')
@section('tag', 'Account security')
@section('preheader', $code ? "Your code is {$code}. It expires in {$minutes} minutes." : "Reset link inside. It expires in {$minutes} minutes.")

@section('heading', 'Reset your password')

@section('body')
    <p style="margin:0 0 12px;">Hi {{ explode(' ', trim($user->name))[0] }},</p>

    @if($code)
        <p style="margin:0 0 16px;">We received a request to reset the password for <strong>{{ $user->email }}</strong>. Enter this code on the reset page:</p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding:6px 0 18px;">
                <div style="display:inline-block; background:#f1f6fd; border:1px solid #cfe0f5; border-radius:12px; padding:16px 26px;">
                    <span style="font-family:Consolas, 'Courier New', monospace; font-size:34px; font-weight:800; letter-spacing:12px; color:#185FA5;">{{ $code }}</span>
                </div>
                <div style="font-size:12px; color:#64748b; margin-top:8px;">Expires in {{ $minutes }} minutes · one use only</div>
            </td></tr>
        </table>
    @else
        <p style="margin:0 0 6px;">We received a request to reset the password for <strong>{{ $user->email }}</strong>. Click the button to choose a new one:</p>

        @include('emails.layouts.partials.button', ['url' => $url, 'label' => 'Choose a new password'])

        <p style="margin:0 0 12px; font-size:12px; color:#64748b;">
            This link expires in {{ $minutes }} minutes and can be used once. If the button doesn't work, copy this address into your browser:<br>
            <a href="{{ $url }}" style="color:#185FA5; word-break:break-all;">{{ $url }}</a>
        </p>
    @endif
@endsection

@section('footnote')
    Didn't ask for this? You can ignore this email. Your password stays the same.
    Never share this {{ $code ? 'code' : 'link' }} with anyone. HR will never ask for it.
@endsection
