<?php

namespace CNRP\InstagramFeed;

<<<<<<< HEAD
=======
use Dymantic\InstagramFeed\Profile;
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use CNRP\InstagramFeed\Models\InstagramPost;
use CNRP\InstagramFeed\Models\InstagramMedia;
use Exception;
use Illuminate\Support\Facades\Log;
<<<<<<< HEAD
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use Illuminate\Support\Facades\DB;

class InstagramFeed
{
    protected InstagramAPI $api;

    public function __construct(InstagramAPI $api)
    {
        $this->api = $api;
=======

class InstagramFeed
{
    protected Profile $profile;

    public function __construct(string $profileName)
    {
        $this->profile = Profile::for($profileName);
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
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

<<<<<<< HEAD
    public function refreshFeed(): void
=======
    public function refreshFeed(int $limit = 20): Collection
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
    {
        try {
            if (!$this->isAuthorized()) {
                throw new Exception('Instagram profile is not authorized.');
            }
            
<<<<<<< HEAD
            $feedData = $this->api->getFeed();
            Log::info('Raw feed data:', ['data' => json_encode($feedData)]);
            
            $this->storeFeed($feedData);
        } catch (Exception $e) {
            Log::error('Error refreshing feed:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }


=======
            $feedData = $this->profile->refreshFeed($limit);
            Log::info('Raw feed data:', ['data' => json_encode($feedData)]);
            
            $this->storeFeed($feedData);
            return $this->getStoredFeed($limit);
        } catch (Exception $e) {
            Log::error('Error refreshing feed:', ['error' => $e->getMessage()]);
            return collect();
        }
    }

>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
    protected function storeFeed($feedData): void
    {
        foreach ($feedData as $post) {
            $instagramPost = InstagramPost::updateOrCreate(
<<<<<<< HEAD
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
=======
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
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
            }
        }
    }

<<<<<<< HEAD
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
=======
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
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
                'error' => $e->getMessage()
            ]);
        }
    }

<<<<<<< HEAD

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

=======
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
    protected function getStoredFeed(int $limit): Collection
    {
        return InstagramPost::with('media')
            ->latest('timestamp')
            ->take($limit)
<<<<<<< HEAD
            ->get();
=======
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
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
    }

    public function isAuthorized(): bool
    {
<<<<<<< HEAD
        return $this->api->isAuthorized();
    }

    public function getAuthUrl(): string
    {
        return $this->api->getAuthUrl();
=======
        return $this->profile->hasInstagramAccess();
    }

    public function completeAuthorization(string $code): void
    {
        $this->profile->requestInstagramAccess($code);
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
    }
}