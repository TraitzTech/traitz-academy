<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Office location (attendance geofence)
    |--------------------------------------------------------------------------
    |
    | Interns must clock in from within `radius_meters` of these coordinates.
    | Distance is verified server-side (haversine). Set enforce_location=false
    | to accept any location (e.g. for remote cohorts or local testing without
    | HTTPS geolocation).
    |
    */

    'office' => [
        'latitude' => env('OFFICE_LATITUDE'),
        'longitude' => env('OFFICE_LONGITUDE'),
        'radius_meters' => (int) env('OFFICE_RADIUS_METERS', 150),
        // Buffer added to the radius to absorb GPS drift (avoids false rejects).
        'tolerance_meters' => (int) env('OFFICE_TOLERANCE_METERS', 100),
        'enforce_location' => (bool) env('INTERNSHIP_ENFORCE_LOCATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance rules
    |--------------------------------------------------------------------------
    */

    // Require the day's logbook entry to be filled before an intern can clock out.
    'require_logbook_before_clock_out' => (bool) env('INTERNSHIP_REQUIRE_LOGBOOK', true),

    /*
    |--------------------------------------------------------------------------
    | Logbook compliance
    |--------------------------------------------------------------------------
    |
    | Interns must submit a logbook entry every working day, independent of
    | whether they clocked in at the office that day. `working_days` uses
    | Carbon's ISO weekday numbering (1 = Monday .. 7 = Sunday).
    |
    */

    'logbook' => [
        'working_days' => [1, 2, 3, 4, 5],
        'reminder_time' => env('INTERNSHIP_LOGBOOK_REMINDER_TIME', '20:00'),
    ],

];
