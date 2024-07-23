<?php

namespace CNRP\InstagramFeed\Filament\Pages;

use Filament\Pages\Page;
<<<<<<< HEAD
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use CNRP\InstagramFeed\InstagramAPI;
use CNRP\InstagramFeed\InstagramFeed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use CNRP\InstagramFeed\Filament\Resources\InstagramPostResource;
=======
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use CNRP\InstagramFeed\Facades\InstagramFeed;
use Illuminate\Support\Facades\Log;
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e

class InstagramManager extends Page 
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationLabel = 'Instagram Feed';
    protected static ?string $title = 'Manage Instagram Feed';
    protected static ?string $slug = 'instagram-feed';
    protected static string $view = 'instagram-feed::filament.pages.manage-instagram-feed';

<<<<<<< HEAD
    public ?string $authUrl = null;
    public bool $isAuthorized = false;
    
    protected $feed;
    public $currentPage = 1;
    public $perPage = 8;

    protected InstagramFeed $instagramFeed;

    public function boot(): void
    {
        $api = new InstagramAPI();
        $this->instagramFeed = new InstagramFeed($api);
    }

    public function mount(): void
    {
        $this->refreshAuthStatus();
        $this->initializeFeed();
=======
    public ?string $profileName = null;
    public ?string $authUrl = null;
    public array $feed = [];
    public bool $isAuthorized = false;

    public function mount(): void
    {
        $this->profileName = config('instagram-feed.profile_name', 'devbyconnor');
        $this->form->fill([
            'profileName' => $this->profileName,
        ]);
        $this->refreshAuthStatus();
        $this->refreshFeed();
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
    }

    protected function getHeaderActions(): array
    {
        return [
<<<<<<< HEAD
            Action::make('createPost')
                ->label('Create Post')
                ->url(InstagramPostResource::getUrl('create'))
                ->color('success'),
=======
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
            Action::make('refreshFeed')
                ->label('Refresh Feed')
                ->action('refreshFeed'),
        ];
    }

<<<<<<< HEAD
    public function getFeed(): LengthAwarePaginator
    {
        if (!$this->feed) {
            $this->initializeFeed();
        }
        return $this->paginateFeed($this->feed);
    }

    protected function initializeFeed(): void
    {
        $this->feed = $this->instagramFeed->getFeed()->map(function ($post) {
            $post->edit_url = InstagramPostResource::getUrl('edit', ['record' => $post->id]);
            return $post;
        });
        Log::info('Feed initialized', ['count' => $this->feed->count()]);
    }

    protected function paginateFeed(Collection $feed): LengthAwarePaginator
    {
        $offset = ($this->currentPage - 1) * $this->perPage;

        return new LengthAwarePaginator(
            $feed->slice($offset, $this->perPage)->values(),
            $feed->count(),
            $this->perPage,
            $this->currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function nextPage()
    {
        $this->currentPage++;
    }

    public function previousPage()
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function refreshFeed(): void
    {
        try {
            // Clear all existing posts
            $this->instagramFeed->clearAllPosts();

            // Refresh the feed
            $this->instagramFeed->refreshFeed();
            $this->initializeFeed();
            $this->currentPage = 1;
            Log::info('Feed cleared and refreshed', ['count' => $this->feed->count()]);
            Notification::make()
                ->title('Feed cleared and refreshed successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error clearing and refreshing feed:', [
=======
    public function refreshFeed(): void
    {
        Log::info('Refreshing feed for profile:', ['profileName' => $this->profileName]);
        try {
            $feedItems = InstagramFeed::refreshFeed();
            
            Log::info('Feed items received:', [
                'count' => $feedItems->count(),
                'first_item' => json_encode($feedItems->first())
            ]);
            
            $this->feed = $feedItems->toArray();
            
            Notification::make()
                ->title('Feed refreshed successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error refreshing feed:', [
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Notification::make()
<<<<<<< HEAD
                ->title('Failed to clear and refresh feed')
=======
                ->title('Failed to refresh feed')
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function refreshAuthStatus(): void
    {
<<<<<<< HEAD
        try {
            $this->isAuthorized = $this->instagramFeed->isAuthorized();
            $this->authUrl = $this->instagramFeed->getAuthUrl();
=======
        Log::info('Refreshing auth status for profile:', ['profileName' => $this->profileName]);
        try {
            $this->isAuthorized = InstagramFeed::isAuthorized();
            $this->authUrl = InstagramFeed::getAuthUrl();
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
        } catch (\Exception $e) {
            Log::error('Error refreshing auth status:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->isAuthorized = false;
            $this->authUrl = null;
        }
    }
}