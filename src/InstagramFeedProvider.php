<?php

namespace CNRP\InstagramFeed;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
<<<<<<< HEAD
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;
=======
use CNRP\InstagramFeed\Filament\Pages\InstagramManager;
use Filament\Facades\Filament;
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e

class InstagramFeedProvider extends PackageServiceProvider
{
    public static string $name = 'instagram-feed';

<<<<<<< HEAD
=======
    public function packageRegistered(): void
    {
        $this->app->bind('instagram-feed', function ($app) {
            $profileName = config('instagram-feed.profile_name', 'devbyconnor');
            return new InstagramFeed($profileName);
        });
    }

>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
    public function configurePackage(Package $package): void
    {
        $package
            ->name('instagram-feed')
            ->hasViews()
<<<<<<< HEAD
            ->hasRoute('web')
            ->hasConfigFile();
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


=======
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

    
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
}