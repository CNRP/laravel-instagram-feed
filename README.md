# Laravel Instagram Feed

A Laravel package for fetching, storing, and displaying Instagram feeds with Filament integration. This package provides an efficient way to manage Instagram content within your Laravel application.

## Features

- OAuth authentication with Instagram
- Support for multiple Instagram profiles
- Fetch and store Instagram feed data
- Store Instagram posts and media as Eloquent models
- Display and manage Instagram feeds in Laravel Filament admin panel
- Automatic conversion of images to WebP format for improved performance

## Installation

You can install the package via composer:

```bash
composer require cnrp/laravel-instagram-feed
```

The package will automatically register its service provider.

## Configuration

The package comes with a default configuration file. If you need to customize these settings, you can publish the config file:

```bash
php artisan vendor:publish --tag="instagram-feed-config"
```

This will create a `config/instagram-feed.php` file in your app's configuration directory. The default configuration looks like this:

```php
<?php

return [
    'client_id' => env('INSTA_CLIENT'),
    'client_secret' => env('INSTA_SECRET'),
    'redirect_uri' => env('INSTAGRAM_REDIRECT_URI', 'auth/instagram/callback'),
    'debug' => [
        'log_info' => env('INSTAGRAM_FEED_LOG_INFO', true),
        'log_errors' => env('INSTAGRAM_FEED_LOG_ERRORS', true),
    ],
];
```

### Configuration Options

- `client_id`: Your Instagram API client ID.
- `client_secret`: Your Instagram API client secret.
- `redirect_uri`: The URI to redirect to after Instagram authentication. Default is 'auth/instagram/callback'.
- `debug`: 
  - `log_info`: Whether to log informational messages.
  - `log_errors`: Whether to log error messages.

Make sure to set the appropriate values in your `.env` file:

```
INSTA_CLIENT=your_client_id
INSTA_SECRET=your_client_secret
```

## Usage

### In Filament Admin Panel

This package provides a Filament page for managing your Instagram feeds. You can access it at `/admin/instagram-manager`.

1. Navigate to the Instagram Manager page in your Filament admin panel.
2. Click on "Add New Profile" to authenticate with Instagram.
3. Once authenticated, you can view and manage multiple Instagram profiles and their feeds.
4. Use the "Refresh Feed" button to manually update the feed for a selected profile.

To publish the styles for the Instagram Manager page, run the following command
`php artisan filament:assets`

### Programmatic Usage

You can also use the package programmatically:

```php
use CNRP\InstagramFeed\Facades\InstagramFeed;

// Get a profile
$profile = \CNRP\InstagramFeed\Models\InstagramProfile::find($profileId);

// Refresh the feed for a profile
InstagramFeed::refreshFeed($profile);

// Get the feed for a profile
$feed = InstagramFeed::getFeed($profile);
```

## Models

The package provides the following Eloquent models:

- `InstagramProfile`: Represents an authenticated Instagram profile
- `InstagramPost`: Represents an individual Instagram post
- `InstagramMedia`: Represents media (images/videos) associated with posts

You can interact with these models using standard Eloquent operations.

## WebP Conversion

Images are automatically converted to WebP format when stored, improving load times and optimizing storage usage.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/cnrp/laravel-instagram-feed/issues) on GitHub.