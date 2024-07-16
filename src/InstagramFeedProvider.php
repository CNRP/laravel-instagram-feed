<?php

namespace CNRP\InstagramFeed;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CNRP\InstagramFeed\Filament\Pages\InstagramManager;
use Filament\Facades\Filament;

class InstagramFeedProvider extends PackageServiceProvider
{
    public static string $name = 'instagram-feed';

    public function packageRegistered(): void
    {
        $this->app->bind('instagram-feed', function ($app) {
            $profileName = config('instagram-feed.profile_name', 'devbyconnor');
            return new InstagramFeed($profileName);
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('instagram-feed')
            ->hasViews()
            ->hasRoute('web');
    }

    public function boot()
    {
        parent::boot();
        
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        Filament::registerPages([
            InstagramManager::class,
        ]);
    }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__.'/../config/instagram-feed.php',
            'instagram-feed'
        );
    }

    
}