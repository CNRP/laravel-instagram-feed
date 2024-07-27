<?php

namespace CNRP\InstagramFeed;

use Illuminate\Support\Collection;
use CNRP\InstagramFeed\Models\InstagramPost;
use CNRP\InstagramFeed\Models\InstagramMedia;
use CNRP\InstagramFeed\Models\InstagramProfile;
use Exception;
use CNRP\InstagramFeed\Traits\InstagramFeedLogger;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Facades\DB;

class InstagramFeed
{
    use InstagramFeedLogger;

    protected InstagramAPI $api;

    public function __construct(InstagramAPI $api)
    {
        $this->api = $api;
    }

    public function getFeed(InstagramProfile $profile, int $limit = 20): Collection
    {
        try {
            if (!$this->isAuthorized($profile)) {
                $this->logInfo('Instagram profile is not authorized, returning stored feed', [
                    'profile_id' => $profile->id,
                ]);
            }
            return $this->getStoredFeed($profile, $limit);
        } catch (Exception $e) {
            $this->logError('Error getting feed', [
                'error' => $e->getMessage(),
                'profile_id' => $profile->id,
            ]);
            return collect();
        }
    }

    public function refreshFeed(InstagramProfile $profile): void
    {
        try {
            if (!$this->isAuthorized($profile)) {
                $this->logError('Instagram profile is not authorized for refresh', [
                    'profile_id' => $profile->id,
                ]);
                throw new Exception('Instagram profile is not authorized.');
            }
            
            $feedData = $this->api->getFeed($profile);
            $this->logInfo('Raw feed data received', [
                'profile_id' => $profile->id,
                'post_count' => count($feedData),
            ]);
            
            $this->storeFeed($profile, $feedData);
        } catch (Exception $e) {
            $this->logError('Error refreshing feed', [
                'error' => $e->getMessage(),
                'profile_id' => $profile->id,
            ]);
            throw $e;
        }
    }

    protected function storeFeed(InstagramProfile $profile, $feedData): void
    {
        foreach ($feedData as $post) {
            $instagramPost = InstagramPost::updateOrCreate(
                [
                    'instagram_profile_id' => $profile->id,
                    'instagram_id' => $post['id']
                ],
                [
                    'type' => $post['media_type'],
                    'caption' => $post['caption'] ?? '',
                    'permalink' => $post['permalink'],
                    'timestamp' => $post['timestamp'],
                ]
            );

            $this->logInfo('Processing post', [
                'post_id' => $post['id'],
                'type' => $post['media_type'],
                'is_carousel' => $post['media_type'] === 'CAROUSEL_ALBUM',
            ]);

            if ($post['media_type'] === 'CAROUSEL_ALBUM' && isset($post['children']['data'])) {
                foreach ($post['children']['data'] as $childMedia) {
                    $this->storeMediaItem($instagramPost, $childMedia);
                }
            } else {
                $this->storeMediaItem($instagramPost, $post);
            }
        }
    }

    protected function storeMediaItem($instagramPost, $mediaItem): void
    {
        try {
            File::ensureDirectoryExists(public_path('images/instagram'));
            File::ensureDirectoryExists(public_path('videos/instagram'));

            $mediaId = $mediaItem['id'];
            $mediaType = $mediaItem['media_type'];
            $mediaUrl = $mediaItem['media_url'];
            $thumbnailUrl = $mediaItem['thumbnail_url'] ?? null;

            if ($mediaType === 'VIDEO') {
                $videoFilename = 'instagram_video_' . $mediaId . '.mp4';
                $videoPath = public_path('videos/instagram/' . $videoFilename);
                $videoContent = file_get_contents($mediaUrl);
                File::put($videoPath, $videoContent);
                $storedUrl = 'videos/instagram/' . $videoFilename;

                if ($thumbnailUrl) {
                    $thumbFilename = 'instagram_thumb_' . $mediaId . '.webp';
                    $thumbPath = public_path('images/instagram/' . $thumbFilename);
                    $manager = new ImageManager(new Driver());
                    $image = $manager->read(file_get_contents($thumbnailUrl));
                    $image->toWebp(90)->save($thumbPath);
                    $thumbnailStoredUrl = 'images/instagram/' . $thumbFilename;
                } else {
                    $thumbnailStoredUrl = null;
                }
            } else {
                $imageFilename = 'instagram_' . $mediaId . '.webp';
                $imagePath = public_path('images/instagram/' . $imageFilename);
                $manager = new ImageManager(new Driver());
                $image = $manager->read(file_get_contents($mediaUrl));
                $image->toWebp(90)->save($imagePath);
                $storedUrl = 'images/instagram/' . $imageFilename;
                $thumbnailStoredUrl = null;
            }

            InstagramMedia::updateOrCreate(
                ['instagram_media_id' => $mediaId],
                [
                    'instagram_post_id' => $instagramPost->id,
                    'url' => $storedUrl,
                    'media_type' => $mediaType,
                    'thumbnail_url' => $thumbnailStoredUrl,
                ]
            );

            $this->logInfo('Media item stored successfully', [
                'instagram_id' => $instagramPost->instagram_id,
                'media_id' => $mediaId,
                'media_type' => $mediaType
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to store media item', [
                'instagram_id' => $instagramPost->instagram_id,
                'media_id' => $mediaId ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getStoredFeed(InstagramProfile $profile, int $limit): Collection
    {
        return InstagramPost::where('instagram_profile_id', $profile->id)
            ->with('media')
            ->latest('timestamp')
            ->take($limit)
            ->get();
    }

    public function isAuthorized(InstagramProfile $profile): bool
    {
        return $profile->is_authorized && $profile->token_expires_at > now();
    }

    public function clearAllPosts(InstagramProfile $profile): void
    {
        try {
            DB::beginTransaction();

            $mediaFiles = InstagramMedia::whereHas('post', function ($query) use ($profile) {
                $query->where('instagram_profile_id', $profile->id);
            })->get();

            foreach ($mediaFiles as $media) {
                if (File::exists(public_path($media->url))) {
                    File::delete(public_path($media->url));
                }
                if ($media->thumbnail_url && File::exists(public_path($media->thumbnail_url))) {
                    File::delete(public_path($media->thumbnail_url));
                }
            }

            InstagramMedia::whereHas('post', function ($query) use ($profile) {
                $query->where('instagram_profile_id', $profile->id);
            })->delete();

            InstagramPost::where('instagram_profile_id', $profile->id)->delete();

            DB::commit();

            $this->logInfo('All Instagram posts and media cleared successfully', [
                'profile_id' => $profile->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Error clearing Instagram posts and media', [
                'error' => $e->getMessage(),
                'profile_id' => $profile->id,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getAuthUrl(): string
    {
        return $this->api->getAuthUrl();
    }
}