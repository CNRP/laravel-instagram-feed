<?php

use Illuminate\Support\Facades\Route;
use CNRP\InstagramFeed\Http\Controllers\InstagramAuthController;

Route::get('auth/instagram/callback', [InstagramAuthController::class, 'handleCallback'])->name('instagram.callback');

// Route::post('/auth/instagram/deauthorize/{profile}', [InstagramAuthController::class, 'deauthorize'])->name('instagram.deauthorize');