{{-- Shared branded email layout (account + password emails). Sections: title, preheader, heading, body --}}
@php
    $brand = config('company.name');
    $logo  = config('company.logo_url');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $brand)</title>
</head>
<body style="margin:0; padding:0; background:#eef2f7; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1f2937;">

<div style="display:none; max-height:0; overflow:hidden; opacity:0;">@yield('preheader')</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7; padding:28px 12px;">
<tr><td align="center">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #dde3ec;">

        <tr>
            <td style="background:#185FA5; padding:22px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ $logo }}" alt="" height="38" style="height:38px; background:#ffffff; border-radius:6px; padding:3px; vertical-align:middle;">
                            <span style="color:#ffffff; font-size:15px; font-weight:700; vertical-align:middle; margin-left:10px;">{{ $brand }}</span>
                        </td>
                        <td align="right" style="color:#cfe2f7; font-size:12px; vertical-align:middle;">@yield('tag', 'HR System')</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding:28px 28px 8px;">
                <h1 style="margin:0 0 14px; font-size:21px; line-height:1.3; color:#0f172a;">@yield('heading')</h1>
                <div style="font-size:14px; line-height:1.65; color:#374151;">
                    @yield('body')
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding:8px 28px 24px; font-size:12px; color:#6b7280; line-height:1.6;">
                @yield('footnote')
            </td>
        </tr>

        <tr>
            <td style="background:#f8fafc; border-top:1px solid #e5e7eb; padding:14px 28px; font-size:11px; color:#94a3b8; text-align:center;">
                This is an automated message from the {{ $brand }} HR system. Please do not reply to this email.<br>
                &copy; {{ date('Y') }} {{ $brand }}
            </td>
        </tr>

    </table>

</td></tr>
</table>
</body>
</html>
