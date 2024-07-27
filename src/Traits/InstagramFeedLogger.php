<?php

namespace CNRP\InstagramFeed\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

trait InstagramFeedLogger
{
    protected function logInfo(string $message, array $context = null): void
    {
        if ($this->shouldLogInfo()) {
            $context ? Log::info($message, $context) : Log::info($message);
        }
    }

    protected function logError(string $message, array $context = null): void
    {
        if ($this->shouldLogErrors()) {
            $context ? Log::error($message, $context) : Log::error($message);
        }
    }

    protected function shouldLogInfo(): bool
    {
        return Config::get('instagram-feed.debug.log_info', false);
    }

    protected function shouldLogErrors(): bool
    {
        return Config::get('instagram-feed.debug.log_errors', true);
    }
}