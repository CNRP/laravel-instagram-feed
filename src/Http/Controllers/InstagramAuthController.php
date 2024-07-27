<?php

namespace CNRP\InstagramFeed\Http\Controllers;

use Illuminate\Http\Request;
use CNRP\InstagramFeed\InstagramAPI;
use Illuminate\Support\Facades\Cache;
use CNRP\InstagramFeed\Traits\InstagramFeedLogger;
use CNRP\InstagramFeed\Models\InstagramProfile;

class InstagramAuthController
{
    use InstagramFeedLogger;

    protected InstagramAPI $instagramApi;

    public function __construct(InstagramAPI $instagramApi)
    {
        $this->instagramApi = $instagramApi;
    }

    public function handleCallback(Request $request)
    {
        $code = $request->query('code');
        $state = $request->query('state');

        try {
            $profile = $this->instagramApi->handleAuthCallback($code, $state);
            return redirect('/admin/instagram-feed')->with('success', 'Instagram profile successfully authenticated.');
        } catch (\Exception $e) {
            return redirect('/admin/instagram-feed')->with('error', 'Failed to authenticate Instagram profile: ' . $e->getMessage());
        }
    }

    // protected function initiateAuth()
    // {
    //     $authUrl = $this->instagramApi->getAuthUrl();
        
    //     $this->logInfo('Initiating Instagram authentication', [
    //         'auth_url' => $authUrl
    //     ]);

    //     return redirect($authUrl);
    // }

    // public function deauthorize(InstagramProfile $profile)
    // {
    //     try {
    //         $profile->update([
    //             'is_authorized' => false,
    //             'access_token' => null,
    //             'token_expires_at' => null,
    //         ]);

    //         $this->logInfo('Instagram profile deauthorized', [
    //             'profile_id' => $profile->id,
    //             'username' => $profile->username
    //         ]);

    //         return redirect('/admin/instagram-feed')->with('success', 'Instagram profile successfully deauthorized.');
    //     } catch (\Exception $e) {
    //         $this->logError('Failed to deauthorize Instagram profile', [
    //             'profile_id' => $profile->id,
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return redirect('/admin/instagram-feed')->with('error', 'Failed to deauthorize Instagram profile: ' . $e->getMessage());
    //     }
    // }
}