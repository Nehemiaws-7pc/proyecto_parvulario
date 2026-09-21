<?php

return [
    'enabled' => (bool) env('DEMO_DATA_ENABLED', false),

    'user_password' => env('DEMO_USER_PASSWORD'),

    'reset_passwords' => (bool) env('DEMO_RESET_PASSWORDS', false),
];
