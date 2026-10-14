<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Annual leave entitlement
    |--------------------------------------------------------------------------
    | Default number of annual leave days per year for a new employee.
    | HR can override it per employee on the employee form.
    */
    'annual_entitlement' => (float) env('LEAVE_ANNUAL_ENTITLEMENT', 24),

    /*
    |--------------------------------------------------------------------------
    | Leave types that reduce the employee's leave balance
    |--------------------------------------------------------------------------
    | Only these types are deducted from the balance on final approval.
    | Sick, Maternity, Compassionate, Unpaid etc. are tracked but not deducted.
    | Example .env value: LEAVE_DEDUCTIBLE_TYPES="Annual,Study"
    */
    'deductible_types' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('LEAVE_DEDUCTIBLE_TYPES', 'Annual'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Negative balances
    |--------------------------------------------------------------------------
    | false = employees cannot apply for more deductible days than they have,
    | and HR must tick "override" to approve beyond the balance.
    */
    'allow_negative_balance' => (bool) env('LEAVE_ALLOW_NEGATIVE_BALANCE', false),

    /*
    |--------------------------------------------------------------------------
    | Which days are NOT counted as leave days
    |--------------------------------------------------------------------------
    | Defaults for every new request. Zambian public holidays are generated
    | automatically and can be managed under Leave Management → Public Holidays.
    */
    'exclude_sundays'   => (bool) env('LEAVE_EXCLUDE_SUNDAYS', true),
    'exclude_saturdays' => (bool) env('LEAVE_EXCLUDE_SATURDAYS', false),
    'exclude_holidays'  => (bool) env('LEAVE_EXCLUDE_HOLIDAYS', true),

    // Let employees switch these options on the leave form (HR always can).
    'employee_can_change_day_rules' => (bool) env('LEAVE_EMPLOYEE_CAN_CHANGE_DAY_RULES', true),

    /*
    |--------------------------------------------------------------------------
    | New leave year
    |--------------------------------------------------------------------------
    | Maximum unused days carried into the next year when HR runs
    | "Start new leave year". null = carry everything.
    */
    'carry_forward_max' => env('LEAVE_CARRY_FORWARD_MAX', 10),

    // Balances at or below this are flagged "low" in reports.
    'low_balance_threshold' => (float) env('LEAVE_LOW_BALANCE_THRESHOLD', 3),

];
