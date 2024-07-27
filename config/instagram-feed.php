<?php

return [
    'profile_name' => env('INSTAGRAM_PROFILE_NAME', 'devbyconnor'),
    'client_id' => env('INSTA_CLIENT'),
    'client_secret' => env('INSTA_SECRET'),
    'redirect_uri' => env('INSTAGRAM_REDIRECT_URI', 'auth/instagram/callback'),
    'debug' => [
        'log_info' => env('INSTAGRAM_FEED_LOG_INFO', true),
        'log_errors' => env('INSTAGRAM_FEED_LOG_ERRORS', true),
    ],
];
