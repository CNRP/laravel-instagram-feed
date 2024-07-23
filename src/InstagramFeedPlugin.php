<?php

namespace CNRP\InstagramFeed;

use CNRP\InstagramFeed\Filament\Pages\InstagramManager;
use CNRP\InstagramFeed\Filament\Resources\InstagramPostResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;

class InstagramFeedPlugin implements Plugin
{
    public function getId(): string
    {
        return 'instagram-feed';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                InstagramManager::class,
            ])->resources([
                InstagramPostResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // This method is required by the Plugin interface
        // You can add any boot-time logic here if needed
    }

}