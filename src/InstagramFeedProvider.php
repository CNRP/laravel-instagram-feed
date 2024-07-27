<?php

namespace CNRP\InstagramFeed;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;

class InstagramFeedProvider extends PackageServiceProvider
{
    public static string $name = 'instagram-feed';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('instagram-feed')
            ->hasViews()
            ->hasRoute('web')
            ->hasConfigFile()
            ->hasAssets();
    }

    public function packageRegistered(): void
    {
        $this->app->bind('instagram-feed', function ($app) {
            return new InstagramFeed($app->make('config')->get('instagram-feed.profile_name', 'devbyconnor'));
        });
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('instagram-feed-styles', __DIR__ . '/../resources/css/instagram-feed.css'),
        ]);

        $this->registerInstagramDisk();
        
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

    }

    protected function registerInstagramDisk(): void
    {
        $this->app['config']["filesystems.disks.instagram"] = [
            'driver' => 'local',
            'root' => public_path(),
            'url' => env('APP_URL'),
            'visibility' => 'public',
        ];
    }


}