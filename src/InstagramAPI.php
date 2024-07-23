<?php

namespace CNRP\InstagramFeed;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;
use CNRP\InstagramFeed\Models\InstagramProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;

class InstagramAPI
{
    protected ?InstagramProfile $profile;
    protected string $clientId;
    protected string $clientSecret; 
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('instagram-feed.client_id');
        $this->clientSecret = config('instagram-feed.client_secret');
        $this->redirectUri = URL::to(config('instagram-feed.redirect_uri'));
    
        Log::info('InstagramAPI initialized', [
            'redirect_uri' => $this->redirectUri,
            'client_id_set' => !empty($this->clientId),
            'client_secret_set' => !empty($this->clientSecret),
        ]);
    
        if (!$this->clientId || !$this->clientSecret) {
            Log::error('Instagram API configuration error', [
                'client_id' => $this->clientId ? 'set' : 'not set',
                'client_secret' => $this->clientSecret ? 'set' : 'not set',
            ]);
            throw new Exception('Instagram client ID or secret is not set in the configuration.');
        }
    }

    public function isAuthorized(): bool
    {
        $this->profile = InstagramProfile::where('is_authorized', true)
            ->where('token_expires_at', '>', now())
            ->first();

        $isAuthorized = $this->profile !== null;
        
        Log::info('Checking authorization status', [
            'is_authorized' => $isAuthorized,
            'profile_exists' => (bool)$this->profile,
            'token_expires_at' => $this->profile ? $this->profile->token_expires_at : null,
        ]);
        
        return $isAuthorized;
    }

    public function getFeed(int $limit = 20): Collection
    {
        if (!$this->isAuthorized()) {
            throw new Exception('Profile is not authorized');
        }
    
        try {
            $feedData = $this->fetchFeedFromApi($limit);
            Log::info('Feed fetched successfully', ['post_count' => count($feedData)]);
            return collect($feedData);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Error validating access token')) {
                if ($this->refreshToken()) {
                    // Retry fetching feed after token refresh
                    return $this->getFeed($limit);
                }
            }
            Log::error('Error getting feed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function fetchFeedFromApi(int $limit): array
    {
        $url = "https://graph.instagram.com/v12.0/{$this->profile->user_id}/media";
        $response = Http::get($url, [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,children{media_type,media_url,thumbnail_url}',
            'access_token' => $this->profile->access_token,
            'limit' => $limit,
        ]);

        if (!$response->successful()) {
            Log::error('Failed to fetch Instagram feed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to fetch Instagram feed: ' . $response->body());
        }

        return $response->json()['data'];
    }
    
    public function getAuthUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        Cache::put('instagram_auth_state', $state, now()->addMinutes(10));

        Log::info('Generated Instagram auth URL', [
            'state' => $state,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri
        ]);

        return "https://api.instagram.com/oauth/authorize?" . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'user_profile,user_media',
            'response_type' => 'code',
            'state' => $state
        ]);
    }

    public function handleAuthCallback(string $code, string $state): bool
    {
        $cachedState = Cache::get('instagram_auth_state');
        
        Log::info('Handling Instagram auth callback', [
            'received_state' => $state,
            'cached_state' => $cachedState
        ]);

        if ($state !== $cachedState) {
            Log::warning('Invalid state parameter', [
                'received_state' => $state,
                'cached_state' => $cachedState
            ]);
            throw new Exception('Invalid state parameter');
        }

        Cache::forget('instagram_auth_state');

        try {
            $tokenData = $this->exchangeCodeForToken($code);
            $userProfile = $this->fetchUserProfile($tokenData['access_token']);

            // Default expiration to 60 days if not provided
            $expiresIn = $tokenData['expires_in'] ?? 5184000; // 60 days in seconds

            $this->profile = InstagramProfile::updateOrCreate(
                ['user_id' => $tokenData['user_id']],
                [
                    'username' => $userProfile['username'],
                    'access_token' => $tokenData['access_token'],
                    'token_expires_at' => Carbon::now()->addSeconds($expiresIn),
                    'is_authorized' => true,
                ]
            );

            Log::info('Instagram profile created/updated', [
                'username' => $userProfile['username'],
                'user_id' => $tokenData['user_id'],
                'expires_in' => $expiresIn,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Error handling auth callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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

        Log::info('Attempting to exchange code for token', [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'code_length' => strlen($code),
        ]);

        try {
            $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', $params);

            Log::info('Raw response from Instagram', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('Failed to exchange code for token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'sent_params' => array_merge($params, ['client_secret' => '******']),
                ]);
                throw new Exception('Failed to exchange code for token: ' . $response->body());
            }

            $responseData = $response->json();
            Log::info('Successfully exchanged code for token', [
                'user_id' => $responseData['user_id'] ?? 'not provided',
                'response_data' => $responseData,
            ]);

            return $responseData;
        } catch (Exception $e) {
            Log::error('Exception during code exchange', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function fetchUserProfile(string $accessToken): array
    {
        $response = Http::get('https://graph.instagram.com/me', [
            'fields' => 'id,username,account_type',
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error('Failed to fetch user profile', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Failed to fetch user profile: ' . $response->body());
        }

        return $response->json();
    }

    // public function getPostDetails(string $mediaId): array
    // {
    //     if (!$this->isAuthorized()) {
    //         throw new Exception('Profile is not authorized');
    //     }

    //     $url = "https://graph.instagram.com/{$mediaId}";
    //     $response = Http::get($url, [
    //         'fields' => 'id,media_type,media_url,thumbnail_url,permalink,caption,children{media_type,media_url,thumbnail_url}',
    //         'access_token' => $this->profile->access_token,
    //     ]);

    //     if (!$response->successful()) {
    //         Log::error('Failed to fetch Instagram post details', [
    //             'media_id' => $mediaId,
    //             'status' => $response->status(),
    //             'body' => $response->body(),
    //         ]);
    //         throw new Exception('Failed to fetch Instagram post details: ' . $response->body());
    //     }

    //     return $response->json();
    // }

    public function refreshToken(): bool
    {
        if (!$this->profile || !$this->profile->access_token) {
            Log::warning('Attempted to refresh token for non-existent profile');
            return false;
        }

        $response = Http::get('https://graph.instagram.com/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $this->profile->access_token,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->profile->access_token = $data['access_token'];
            $this->profile->token_expires_at = Carbon::now()->addSeconds($data['expires_in']);
            $this->profile->save();
            Log::info('Token refreshed successfully', [
                'expires_at' => $this->profile->token_expires_at,
            ]);
            return true;
        } else {
            Log::error('Failed to refresh token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }
    }
}