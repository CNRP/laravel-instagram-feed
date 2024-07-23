<?php

use Illuminate\Support\Facades\Route;
<<<<<<< HEAD
use CNRP\InstagramFeed\Http\Controllers\InstagramAuthController;

Route::get('/auth/instagram', [InstagramAuthController::class, 'handleCallback'])->name('instagram.callback');
=======
use Dymantic\InstagramFeed\InstagramFeed;


// Route::get('auth/instagram', [InstagramFeed::class, 'handleAuthCallback'])
//     ->name('instagram-feed-callback');

// Route::get('instagram-auth-success', function () {
//     return redirect()->route('filament.pages.manage-instagram-feed')
//         ->with('status', 'Instagram authorization successful!');
// })->name('instagram-auth-success');
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
