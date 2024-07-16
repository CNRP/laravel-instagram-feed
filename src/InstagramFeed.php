<?php

namespace CNRP\InstagramFeed;

use Dymantic\InstagramFeed\Profile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use CNRP\InstagramFeed\Models\InstagramPost;
use CNRP\InstagramFeed\Models\InstagramMedia;
use Exception;
use Illuminate\Support\Facades\Log;

class InstagramFeed
{
    protected Profile $profile;

    public function __construct(string $profileName)
    {
        $this->profile = Profile::for($profileName);
    }

    public function getFeed(int $limit = 20): Collection
    {
        try {
            if (!$this->isAuthorized()) {
                throw new Exception('Instagram profile is not authorized.');
            }
            return $this->getStoredFeed($limit);
        } catch (Exception $e) {
            Log::error('Error getting feed:', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    public function refreshFeed(int $limit = 20): Collection
    {
        try {
            if (!$this->isAuthorized()) {
                throw new Exception('Instagram profile is not authorized.');
            }
            
            $feedData = $this->profile->refreshFeed($limit);
            Log::info('Raw feed data:', ['data' => json_encode($feedData)]);
            
            $this->storeFeed($feedData);
            return $this->getStoredFeed($limit);
        } catch (Exception $e) {
            Log::error('Error refreshing feed:', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    protected function storeFeed($feedData): void
    {
        foreach ($feedData as $post) {
            $instagramPost = InstagramPost::updateOrCreate(
                ['instagram_id' => $post->id],  // This is the long Instagram ID
                [
                    'type' => $post->type ?? '',
                    'caption' => $post->caption ?? '',
                    'permalink' => $post->permalink ?? '',
                    'timestamp' => $post->timestamp ?? now(),
                ]
            );

            if (($post->type ?? '') === 'image') {
                $this->storeMediaItem($instagramPost, $post);
            } elseif (($post->type ?? '') === 'carousel') {
                foreach ($post->carousel_media ?? [] as $index => $mediaItem) {
                    $this->storeMediaItem($instagramPost, $mediaItem, $index);
                }
            }
        }
    }

    protected function storeMediaItem($instagramPost, $mediaItem, $index = null): void
    {
        $url = $mediaItem->media_url ?? $mediaItem->url ?? null;
        if (!$url) {
            Log::error('No URL found for media item', ['instagram_id' => $instagramPost->instagram_id]);
            return;
        }

        $filename = 'instagram_' . $instagramPost->instagram_id;
        if ($index !== null) {
            $filename .= '_' . $index;
        }
        $filename .= '.jpg';

        $path = public_path('images/instagram/' . $filename);

        try {
            File::ensureDirectoryExists(public_path('images/instagram'));

            if (!File::exists($path)) {
                $imageContents = file_get_contents($url);
                File::put($path, $imageContents);
            }

            InstagramMedia::updateOrCreate(
                [
                    'instagram_post_id' => $instagramPost->id,
                    'url' => 'images/instagram/' . $filename,
                ],
                [
                    'media_type' => $mediaItem->media_type ?? $mediaItem->type ?? '',
                    'thumbnail_url' => $mediaItem->thumbnail_url ?? null,
                ]
            );

            Log::info('Media item stored successfully', ['instagram_id' => $instagramPost->instagram_id, 'path' => $path]);
        } catch (\Exception $e) {
            Log::error('Failed to store media item', [
                'instagram_id' => $instagramPost->instagram_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getStoredFeed(int $limit): Collection
    {
        return InstagramPost::with('media')
            ->latest('timestamp')
            ->take($limit)
            ->get()
            ->map(function ($post) {
                $mediaUrl = $post->media->first()->url ?? null;
                return (object) [
                    'id' => $post->instagram_id,  // This is the long Instagram ID
                    'type' => $post->type,
                    'caption' => $post->caption,
                    'permalink' => $post->permalink,
                    'timestamp' => $post->timestamp,
                    'url' => $mediaUrl ? asset($mediaUrl) : null,
                    'carousel_media' => $post->media->map(function ($media) {
                        return (object) [
                            'id' => $media->id,
                            'media_type' => $media->media_type,
                            'media_url' => asset($media->url),
                            'thumbnail_url' => $media->thumbnail_url,
                        ];
                    })->all(),
                ];
            });
    }

    public function getAuthUrl(?string $redirectUri = null): string
    {
        return $this->profile->getInstagramAuthUrl($redirectUri);
    }

    public function isAuthorized(): bool
    {
        return $this->profile->hasInstagramAccess();
    }

    public function completeAuthorization(string $code): void
    {
        $this->profile->requestInstagramAccess($code);
    }
}