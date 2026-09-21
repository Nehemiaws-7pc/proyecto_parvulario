<?php

return [
    'enabled' => (bool) env('DEMO_DATA_ENABLED', false),

    'user_password' => env('DEMO_USER_PASSWORD') ?: 'Demo2026!',

    'reset_passwords' => filter_var(env('DEMO_RESET_PASSWORDS', false), FILTER_VALIDATE_BOOL),
];
