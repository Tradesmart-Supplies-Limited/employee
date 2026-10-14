@extends('emails.layouts.brand')

@section('title', 'Password changed')
@section('tag', 'Account security')
@section('preheader', 'Your password was just changed.')

@section('heading', 'Your password was changed')

@section('body')
    <p style="margin:0 0 12px;">Hi {{ explode(' ', trim($user->name))[0] }},</p>
    <p style="margin:0 0 16px;">
        The password for <strong>{{ $user->email }}</strong> was reset on
        <strong>{{ $when->format('l, d M Y \a\t H:i') }}</strong>{{ $ip ? ' from IP address ' . $ip : '' }}.
    </p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px;">
        <tr><td style="padding:12px 16px; font-size:13px; color:#7f1d1d; line-height:1.6;">
            <strong>Wasn't you?</strong> Reset your password straight away at
            <a href="{{ $forgotUrl }}" style="color:#b91c1c;">{{ $forgotUrl }}</a> and tell HR.
        </td></tr>
    </table>
@endsection

@section('footnote')
    If this was you, there's nothing else to do.
@endsection
