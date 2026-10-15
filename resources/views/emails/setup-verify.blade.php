@extends('emails.layouts.brand')

@section('title', 'Confirm your email')
@section('tag', 'System setup')
@section('preheader', "Your setup code is {$code}. It expires in {$minutes} minutes.")

@section('heading', 'Confirm your email to finish setting up')

@section('body')
    <p style="margin:0 0 12px;">Hi {{ explode(' ', trim($name))[0] }},</p>
    <p style="margin:0 0 16px;">You're creating the first Admin login for the {{ config('company.name') }} HR system with <strong>{{ $email }}</strong>. Enter this code on the setup page:</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center" style="padding:6px 0 18px;">
            <div style="display:inline-block; background:#f1f6fd; border:1px solid #cfe0f5; border-radius:12px; padding:16px 26px;">
                <span style="font-family:Consolas, 'Courier New', monospace; font-size:34px; font-weight:800; letter-spacing:12px; color:#185FA5;">{{ $code }}</span>
            </div>
            <div style="font-size:12px; color:#64748b; margin-top:8px;">Expires in {{ $minutes }} minutes · one use only</div>
        </td></tr>
    </table>

    <p style="margin:0 0 12px;">Once it's confirmed you'll choose a password, and this address becomes the system's Admin login.</p>
@endsection

@section('footnote')
    Didn't start setting up the HR system? You can ignore this email; nothing is created until the code is entered.
@endsection
