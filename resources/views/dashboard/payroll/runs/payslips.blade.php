<!DOCTYPE html>
<html>
<head>
    <title>{{ $run->period }} Payroll Payslips</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #fff;
            padding: 20px;
        }

        .page {
            page-break-after: always;
            margin-bottom: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company {
            font-size: 18px;
            font-weight: bold;
        }

        .title {
            font-size: 14px;
            color: #555;
        }

        .box {
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 10px;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        td {
            padding: 4px;
        }

        .right {
            text-align: right;
        }

        .net {
            font-size: 16px;
            font-weight: bold;
            color: green;
        }

        @media print {
            button { display: none; }
        }

    </style>

</head>

<body onload="window.print()">

    {{-- PRINT BUTTON --}}
    <button onclick="window.print()"
            style="margin-bottom:20px;padding:10px 15px;">
        Print Payslips
    </button>

    <h2 style="text-align:center;">
        {{ $run->period }} Payroll Payslips
    </h2>

    @foreach($run->payrolls as $payroll)

        <div class="page">

            <div class="header">
                <div class="company">TRADESMART SUPPLIES LIMITED</div>
                <div class="title">Employee Payslip</div>
            </div>

            <div class="box">

                <div class="row">
                    <div>
                        <strong>Employee:</strong><br>
                        {{ $payroll->employee->first_name }}
                        {{ $payroll->employee->last_name }}<br>

                        <strong>ID:</strong> {{ $payroll->employee->employee_id }}<br>
                        <strong>Position:</strong> {{ $payroll->employee->position }}
                    </div>

                    <div class="right">
                        <strong>Period:</strong> {{ $run->period }}<br>
                        <strong>Branch:</strong> {{ $payroll->branch }}<br>
                        <strong>Department:</strong> {{ $payroll->cost_centre }}
                    </div>
                </div>

                <hr>

                <div class="row">

                    <div style="width:48%;">
                        <h4>Earnings</h4>

                        <table>
                            @foreach($payroll->items->where('type','earning') as $item)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td class="right">K {{ number_format($item->amount,2) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>

                    <div style="width:48%;">
                        <h4>Deductions</h4>

                        <table>
                            @foreach($payroll->items->where('type','deduction') as $item)
                                <tr>
                                    <td>{{ $item->description }}</td>
                                    <td class="right">K {{ number_format($item->amount,2) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>

                </div>

                <hr>

                <div class="row">

                    <div>
                        <strong>Total Earnings:</strong><br>
                        K {{ number_format($payroll->total_income,2) }}
                    </div>

                    <div>
                        <strong>Total Deductions:</strong><br>
                        K {{ number_format($payroll->total_deductions,2) }}
                    </div>

                    <div class="net">
                        Net Pay: K {{ number_format($payroll->net_pay,2) }}
                    </div>

                </div>

            </div>

        </div>

    @endforeach

</body>
</html>