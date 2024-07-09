<?php

namespace CNRP\InstagramFeed;

use Dymantic\InstagramFeed\Profile;
use Illuminate\Support\Collection;
use Exception;

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
            return $this->profile->feed($limit);
        } catch (Exception $e) {
            // Log the error or handle it as needed
            return collect();
        }
    }

    public function refreshFeed(int $limit = 20): Collection
    {
        try {
            if (!$this->isAuthorized()) {
                throw new Exception('Instagram profile is not authorized.');
            }
            return $this->profile->refreshFeed($limit);
        } catch (Exception $e) {
            // Log the error or handle it as needed
            return collect();
        }
    }

    public function getAuthUrl(string $redirectUri = null): string
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
