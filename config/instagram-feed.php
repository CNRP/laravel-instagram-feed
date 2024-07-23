<?php

return [
    'profile_name' => env('INSTAGRAM_PROFILE_NAME', 'devbyconnor'),
    'client_id' => env('INSTA_CLIENT'),
    'client_secret' => env('INSTA_SECRET'),
    'redirect_uri' => env('INSTAGRAM_REDIRECT_URI', 'auth/instagram'),
];
