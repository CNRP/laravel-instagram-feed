<?php

namespace CNRP\InstagramFeed\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use CNRP\InstagramFeed\InstagramAPI;
use CNRP\InstagramFeed\InstagramFeed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use CNRP\InstagramFeed\Filament\Resources\InstagramPostResource;

class InstagramManager extends Page 
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationLabel = 'Instagram Feed';
    protected static ?string $title = 'Manage Instagram Feed';
    protected static ?string $slug = 'instagram-feed';
    protected static string $view = 'instagram-feed::filament.pages.manage-instagram-feed';

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
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createPost')
                ->label('Create Post')
                ->url(InstagramPostResource::getUrl('create'))
                ->color('success'),
            Action::make('refreshFeed')
                ->label('Refresh Feed')
                ->action('refreshFeed'),
        ];
    }

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Notification::make()
                ->title('Failed to clear and refresh feed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function refreshAuthStatus(): void
    {
        try {
            $this->isAuthorized = $this->instagramFeed->isAuthorized();
            $this->authUrl = $this->instagramFeed->getAuthUrl();
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