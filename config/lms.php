<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Instalment scheduling
    |--------------------------------------------------------------------------
    |
    | After a successful partial instalment payment, the next expected payment
    | date is set to now plus this many days. Reminders are sent three calendar
    | days before that date.
    |
    */
    'instalment_interval_days' => (int) env('LMS_INSTALMENT_INTERVAL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Failed instalment payments before suspension
    |--------------------------------------------------------------------------
    |
    | When a learner on an instalment plan has a payment declined this many
    | times in a row (while they still owe a balance), course access is set to
    | suspended until they pay successfully or staff restores access.
    |
    */
    'failed_instalment_payments_before_suspend' => (int) env('LMS_FAILED_INSTALMENTS_BEFORE_SUSPEND', 3),

];
