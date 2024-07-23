<?php

use Illuminate\Support\Facades\Route;
use CNRP\InstagramFeed\Http\Controllers\InstagramAuthController;

Route::get('/auth/instagram', [InstagramAuthController::class, 'handleCallback'])->name('instagram.callback');