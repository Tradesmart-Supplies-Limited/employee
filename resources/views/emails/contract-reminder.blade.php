<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract Ending Reminder</title>
</head>
<body style="margin:0; padding:0; background:#f4f5f7; font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7; padding:24px 0;">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                       style="background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:#111827; padding:20px 28px;">
                            <span style="color:#ffffff; font-size:16px; font-weight:600;">
                                Contract Ending Reminder
                            </span>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:24px 28px 8px 28px;">
                            <p style="margin:0 0 4px 0; color:#374151; font-size:14px;">
                                Trigger: <strong>{{ $label }}</strong>
                            </p>
                            <p style="margin:0 0 16px 0; color:#6b7280; font-size:13px;">
                                {{ $employees->count() }} employee(s) with a contract ending soon.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 28px 24px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="border-collapse:collapse; font-size:13px;">
                                <thead>
                                    <tr>
                                        <th align="left" style="padding:8px; border-bottom:2px solid #e5e7eb; color:#6b7280; font-size:12px; text-transform:uppercase;">Employee</th>
                                        <th align="left" style="padding:8px; border-bottom:2px solid #e5e7eb; color:#6b7280; font-size:12px; text-transform:uppercase;">Department</th>
                                        <th align="left" style="padding:8px; border-bottom:2px solid #e5e7eb; color:#6b7280; font-size:12px; text-transform:uppercase;">Contract Ends</th>
                                        <th align="left" style="padding:8px; border-bottom:2px solid #e5e7eb; color:#6b7280; font-size:12px; text-transform:uppercase;">Days Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employees as $employee)
                                    @php
                                        $contractEnd = \Carbon\Carbon::parse($employee->contract_end);
                                        $daysLeft = (int) now()->startOfDay()->diffInDays($contractEnd->startOfDay(), false);
                                    @endphp
                                    <tr>
                                        <td style="padding:8px; border-bottom:1px solid #f3f4f6; color:#111827;">
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                            <div style="color:#9ca3af; font-size:11px;">{{ $employee->position }}</div>
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f3f4f6; color:#374151;">
                                            {{ $employee->department ?? '—' }}
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f3f4f6; color:#374151;">
                                            {{ $contractEnd->format('M d, Y') }}
                                        </td>
                                        <td style="padding:8px; border-bottom:1px solid #f3f4f6;">
                                            <span style="color:{{ $daysLeft <= 7 ? '#dc2626' : '#374151' }}; font-weight:600;">
                                                {{ $daysLeft }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:16px 28px; background:#f9fafb; border-top:1px solid #e5e7eb;">
                            <p style="margin:0; color:#9ca3af; font-size:11px;">
                                This is an automated reminder. Manage recipients and schedule under Settings &rarr; Contract Reminders.
                            </p>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
