<?php

namespace CNRP\InstagramFeed;

use Illuminate\Support\Facades\Http;
use Exception;
use CNRP\InstagramFeed\Models\InstagramProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use CNRP\InstagramFeed\Traits\InstagramFeedLogger;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;

class InstagramAPI
{
    use InstagramFeedLogger;

    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('instagram-feed.client_id');
        $this->clientSecret = config('instagram-feed.client_secret');
        $this->redirectUri = URL::to(Config::get('instagram-feed.redirect_uri'));
    }

    protected function getTokenInfo(string $accessToken): array
    {
        $response = Http::get('https://graph.instagram.com/debug_token', [
            'input_token' => $accessToken,
            'access_token' => $this->clientId . '|' . $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to fetch token info: ' . $response->body());
        }

        return $response->json();
    }


    public function handleAuthCallback(string $code, string $state): InstagramProfile
    {
        $cachedState = Cache::get('instagram_auth_state');
        
        if ($state !== $cachedState) {
            $this->logError('Invalid state parameter', [
                'received_state' => $state,
                'cached_state' => $cachedState
            ]);
            throw new Exception('Invalid state parameter');
        }

        Cache::forget('instagram_auth_state');

        $tokenData = $this->exchangeCodeForToken($code);

        $userProfile = $this->fetchUserProfile($tokenData['access_token']);
        // dd($userProfile);

        $expiresIn = $tokenData['expires_in'] ?? 0; // 60 days in seconds

        $profile = InstagramProfile::updateOrCreate(
            ['user_id' => $tokenData['user_id']],
            [
                'username' => $userProfile['username'],
                'access_token' => $tokenData['access_token'],
                'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
                'is_authorized' => true,
            ]
        );

        $this->logInfo('Instagram profile created/updated', [
            'username' => $userProfile['username'],
            'user_id' => $tokenData['user_id'],
        ]);

        return $profile;
    }


    public function getAuthUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        Cache::put('instagram_auth_state', $state, now()->addMinutes(10));

        return "https://api.instagram.com/oauth/authorize?" . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'user_profile,user_media',
            'response_type' => 'code',
            'state' => $state
        ]);
    }


    protected function exchangeCodeForToken(string $code): array
    {
        $params = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];
    
        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', $params);
    
        if (!$response->successful()) {
            throw new Exception('Failed to exchange code for token: ' . $response->body());
        }
    
        $shortLivedTokenData = $response->json();
    
        // Exchange short-lived token for long-lived token
        $longLivedTokenResponse = Http::get('https://graph.instagram.com/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->clientSecret,
            'access_token' => $shortLivedTokenData['access_token'],
        ]);
    
        if (!$longLivedTokenResponse->successful()) {
            throw new Exception('Failed to exchange for long-lived token: ' . $longLivedTokenResponse->body());
        }
    
        $longLivedTokenData = $longLivedTokenResponse->json();
    
        return [
            'access_token' => $longLivedTokenData['access_token'],
            'user_id' => $shortLivedTokenData['user_id'],
            'expires_in' => $longLivedTokenData['expires_in'],
        ];
    }

    protected function fetchUserProfile(string $accessToken): array
    {
        $response = Http::get('https://graph.instagram.com/me', [
            'fields' => 'id,username,account_type',
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            $this->logError('Failed to fetch user profile', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to fetch user profile: ' . $response->body());
        }

        return $response->json();
    }

    public function refreshTokenIfNeeded(InstagramProfile $profile): bool
    {
        if ($profile->token_expires_at <= now()) {
            return $this->refreshToken($profile);
        }
        return true;
    }

    public function getFeed(InstagramProfile $profile, int $limit = 20): array
    {
        try {
            return $this->fetchFeed($profile, $limit);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Error validating access token') !== false) {
                $this->logInfo('Token validation failed', ['profile_id' => $profile->id]);
            }
            throw $e;
        }
    }

    protected function fetchFeed(InstagramProfile $profile, int $limit): array
    {
        $url = "https://graph.instagram.com/v12.0/{$profile->user_id}/media";
        $response = Http::get($url, [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,children{media_type,media_url,thumbnail_url}',
            'access_token' => $profile->access_token,
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            $this->logError('Failed to fetch Instagram feed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to fetch Instagram feed: ' . $response->body());
        }

        $this->logInfo('Successfully fetched Instagram feed', [
            'profile_id' => $profile->id,
            'post_count' => count($response->json()['data']),
        ]);

        return $response->json()['data'];
    }

    public function refreshToken(InstagramProfile $profile): bool
    {
        $response = Http::get('https://graph.instagram.com/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $profile->access_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $profile->update([
                'access_token' => $data['access_token'],
                'token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
            ]);
            $this->logInfo('Token refreshed successfully', [
                'profile_id' => $profile->id,
                'new_expiry' => $profile->token_expires_at,
            ]);
            return true;
        }

        $this->logError('Failed to refresh token', [
            'profile_id' => $profile->id,
            'response' => $response->body(),
        ]);
        return false;
    }

}