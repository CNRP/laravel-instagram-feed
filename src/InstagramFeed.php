<?php

namespace CNRP\InstagramFeed;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use CNRP\InstagramFeed\Models\InstagramPost;
use CNRP\InstagramFeed\Models\InstagramMedia;
use Exception;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Facades\DB;

class InstagramFeed
{
    protected InstagramAPI $api;

    public function __construct(InstagramAPI $api)
    {
        $this->api = $api;
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

    public function refreshFeed(): void
    {
        try {
            if (!$this->isAuthorized()) {
                throw new Exception('Instagram profile is not authorized.');
            }
            
            $feedData = $this->api->getFeed();
            Log::info('Raw feed data:', ['data' => json_encode($feedData)]);
            
            $this->storeFeed($feedData);
        } catch (Exception $e) {
            Log::error('Error refreshing feed:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }


    protected function storeFeed($feedData): void
    {
        foreach ($feedData as $post) {
            $instagramPost = InstagramPost::updateOrCreate(
                ['instagram_id' => $post['id']],
                [
                    'type' => $post['media_type'],
                    'caption' => $post['caption'] ?? '',
                    'permalink' => $post['permalink'],
                    'timestamp' => $post['timestamp'],
                ]
            );

            Log::info('Processing post', [
                'id' => $post['id'],
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

            Log::info('Media item stored successfully', [
                'instagram_id' => $instagramPost->instagram_id,
                'media_id' => $mediaId,
                'media_type' => $mediaType
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store media item', [
                'instagram_id' => $instagramPost->instagram_id,
                'media_id' => $mediaId ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }


    public function clearAllPosts(): void
    {
        try {
            DB::beginTransaction();

            // Get all media files
            $mediaFiles = InstagramMedia::all();

            // Delete all media files from storage
            foreach ($mediaFiles as $media) {
                if (File::exists(public_path($media->url))) {
                    File::delete(public_path($media->url));
                }
                if ($media->thumbnail_url && File::exists(public_path($media->thumbnail_url))) {
                    File::delete(public_path($media->thumbnail_url));
                }
            }

            // Delete all records from the database
            InstagramMedia::query()->delete();
            InstagramPost::query()->delete();

            DB::commit();

            Log::info('All Instagram posts and media cleared successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error clearing Instagram posts and media:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function getStoredFeed(int $limit): Collection
    {
        return InstagramPost::with('media')
            ->latest('timestamp')
            ->take($limit)
            ->get();
    }

    public function isAuthorized(): bool
    {
        return $this->api->isAuthorized();
    }

    public function getAuthUrl(): string
    {
        return $this->api->getAuthUrl();
    }
}