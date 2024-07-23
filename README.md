# Laravel Instagram Feed

A Laravel package for fetching, storing, and displaying Instagram feeds with Filament.

## Features

- Fetch Instagram feed data
- Store Instagram posts and media in the database
- Convert images to WebP format for improved performance
- Display Instagram feed in Laravel Filament admin panel
- Automatic refresh of feed data

## Installation

You can install the package via composer:

```bash
composer require cnrp/laravel-instagram-feed
```

## Configuration

1. Publish the config file:

```bash
php artisan vendor:publish --tag="instagram-feed-config"
```

2. Set up your Instagram credentials in your `.env` file:

```
INSTAGRAM_PROFILE_NAME=your_instagram_username
```

3. Run the migrations:

```bash
php artisan migrate
```

## Usage

### In Filament Admin Panel

This package provides a Filament page for managing your Instagram feed. You can access it at `/admin/instagram-feed`.

### Programmatically

You can use the `InstagramFeed` facade in your code:

```php
use CNRP\InstagramFeed\Facades\InstagramFeed;

// Refresh the feed
InstagramFeed::refreshFeed();

// Get the stored feed
$feed = InstagramFeed::getFeed();
```

## WebP Conversion

Images are automatically converted to WebP format when stored. This improves load times and SEO performance.


## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).