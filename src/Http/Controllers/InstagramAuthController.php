<?php

namespace CNRP\InstagramFeed\Http\Controllers;

use Illuminate\Http\Request;
use CNRP\InstagramFeed\InstagramAPI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class InstagramAuthController
{
    public function handleCallback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');

        $instagramApi = new InstagramAPI();

        try {
            $instagramApi->handleAuthCallback($code, $state);
            Log::info('Instagram authorization successful', [
                'code' => $code,
                'state' => $state
            ]);
        } catch (\Exception $e) {
            Log::error('Instagram authorization failed', [
                'error' => $e->getMessage(),
                'code' => $code,
                'state' => $state,
                'cached_state' => Cache::get('instagram_auth_state'),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return redirect('/admin/instagram-feed-2');
    }
}