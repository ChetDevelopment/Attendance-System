<?php

/**
 * PNC Session Schedule Configuration
 *
 * Default session times for Passerelles Numeriques Cambodia (PNC)
 * Timezone: Asia/Bangkok (UTC+7)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Session Configuration
    |--------------------------------------------------------------------------
    |
    | Default PNC session times - these are used when no sessions are configured
    | in the database or for initial setup.
    |
    */
    'default_sessions' => [
        [
            'name' => 'Session 1',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'order' => 1,
            'late_threshold' => 15,
            'is_active' => true,
            'description' => 'Morning Session - 07:30 AM to 09:00 AM',
        ],
        [
            'name' => 'Session 2',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'order' => 2,
            'late_threshold' => 15,
            'is_active' => true,
            'description' => 'Mid-Morning Session - 10:00 AM to 11:30 AM',
        ],
        [
            'name' => 'Session 3',
            'start_time' => '13:00',
            'end_time' => '14:30',
            'order' => 3,
            'late_threshold' => 15,
            'is_active' => true,
            'description' => 'Afternoon Session - 01:00 PM to 02:30 PM',
        ],
        [
            'name' => 'Session 4',
            'start_time' => '15:30',
            'end_time' => '17:00',
            'order' => 4,
            'late_threshold' => 15,
            'is_active' => true,
            'description' => 'Late Afternoon Session - 03:30 PM to 05:00 PM',
        ],
    ],

    'default_late_threshold' => env('SESSION_LATE_THRESHOLD', 15),

    'timezone' => env('SESSION_TIMEZONE', 'Asia/Bangkok'),

    'validation' => [
        'strict_session_time' => env('SESSION_STRICT_TIME', true),
        'early_checkin_minutes' => env('SESSION_EARLY_CHECKIN', 30),
        'late_checkin_minutes' => env('SESSION_LATE_CHECKIN', 0),
    ],

    'sessions_per_day' => env('SESSION_PER_DAY', 4),

    'cache' => [
        'enabled' => env('SESSION_CACHE_ENABLED', true),
        'ttl' => env('SESSION_CACHE_TTL', 300),
    ],
];
