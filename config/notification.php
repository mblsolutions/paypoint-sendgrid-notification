<?php

return [
    
    /*
     |--------------------------------------------------------------------------
     | Protected Keys
     |--------------------------------------------------------------------------
     |
     | To protect sensitive data being logged in the database in plain text
     | you can add keys to this array that should be masked with an astrix.
     |
     */

     'protected_keys' => [
        'password',
        'password_confirmation',
    ],

    /*
     |--------------------------------------------------------------------------
     | Max Loggable Length
     |--------------------------------------------------------------------------
     |
     | Configure the maximum amount of data (in bytes) that can be logged as part of the
     | request/response header and body elements.
     |
     */

     'max_loggable_length' => env('NOTIFICATION_LOG_MAX_LENGTH', 10024),

     /*
     |--------------------------------------------------------------------------
     | HTTP Client Timeout
     |--------------------------------------------------------------------------
     |
     | The timeout in seconds if the endpoint is not responded.
     |
     */

    'timeout' => env('NOTIFICATION_TIMEOUT', 5),

    /*
     |--------------------------------------------------------------------------
     | Database Migration
     |--------------------------------------------------------------------------
     |
     | The database migration file is created and the generated file could be
     | imported to application.
     |
     */

    'database' => [
        'table' => env('NOTIFICATION_LOGS_TABLE', 'notification_logs'),
    ],

     /*
     |--------------------------------------------------------------------------
     | Authentication Guard
     |--------------------------------------------------------------------------
     |
     | The authentication guard being used by the system e.g. sanctum
     |
     */

    'auth_guard' => null,

    /*
     |--------------------------------------------------------------------------
     | Email Identifier Name
     |--------------------------------------------------------------------------
     |
     | The email identifier being used by the other system to trace back
     |
     */

     'unique_email_identifier' => env('SENDGRID_EMAIL_IDENTIFIER','mblsolutions'),

     /*
     |--------------------------------------------------------------------------
     | Cool Down Period
     |--------------------------------------------------------------------------
     |
     | The cool down period to resend welcome email
     |
     */

     'cooldown' => [
        'minutes' => env('NOTIFICATION_COOLDOWN_IN_MINUTES'),
        'days' => env('NOTIFICATION_COOLDOWN_IN_DAYS'),
        'months' => env('NOTIFICATION_COOLDOWN_IN_MONTHS'),
        'years' => env('NOTIFICATION_COOLDOWN_IN_YEARS',1),
     ],
     
     /*
     |--------------------------------------------------------------------------
     | Email Sending Setting
     |--------------------------------------------------------------------------
     |
     | The send out setting like re-queue
     |
     */

     'retries' => [
        'tries' => env('NOTIFICATION_RETRIES_TIME',3),
        'timeout' => env('NOTIFICATION_RETRIES_TIMEOUT',60),
        'backoff' => env('NOTIFICATION_RETRIES_BACKOFF',60),
     ]


     
];