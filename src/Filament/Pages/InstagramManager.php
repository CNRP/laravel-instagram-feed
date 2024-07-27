<?php

namespace CNRP\InstagramFeed\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use CNRP\InstagramFeed\InstagramAPI;
use CNRP\InstagramFeed\InstagramFeed;
use CNRP\InstagramFeed\Models\InstagramProfile;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use CNRP\InstagramFeed\Traits\InstagramFeedLogger;
use CNRP\InstagramFeed\Filament\Resources\InstagramPostResource;

class InstagramManager extends Page implements HasForms
{
    use InteractsWithForms;
    use InstagramFeedLogger;

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
    public $selectedProfileId;
    public Collection $profiles;

    protected InstagramAPI $instagramApi;
    protected InstagramFeed $instagramFeed;

    public function boot(InstagramAPI $instagramApi, InstagramFeed $instagramFeed): void
    {
        $this->instagramApi = $instagramApi;
        $this->instagramFeed = $instagramFeed;
    }

    public function mount(): void
    {
        $this->profiles = InstagramProfile::all();
        $this->selectedProfileId = $this->profiles->first()->id ?? null;
        $this->refreshAuthStatus();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedProfileId')
                ->label('Select Profile')
                ->options($this->profiles->pluck('username', 'id'))
                ->reactive()
                ->afterStateUpdated(fn () => $this->selectProfile()),
        ];
    }

    public function selectProfile(): void
    {
        $this->refreshAuthStatus();
        $this->feed = null; // Reset feed to trigger re-initialization
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshFeed')
                ->label('Refresh Feed')
                ->action('refreshFeed')
                ->visible(fn () => $this->selectedProfileId && $this->isAuthorized),
            Action::make('addProfile')
                ->label('Add New Profile')
                ->action('addNewProfile'),
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
        if ($this->selectedProfileId) {
            $profile = InstagramProfile::find($this->selectedProfileId);
            $this->feed = $this->instagramFeed->getFeed($profile)->map(function ($post) {
                $post->edit_url = InstagramPostResource::getUrl('edit', ['record' => $post->id]);
                return $post;
            });
            $this->logInfo('Feed initialized', [
                'profile_id' => $profile->id,
                'post_count' => $this->feed->count()
            ]);
        } else {
            $this->feed = collect();
            $this->logInfo('No profile selected, feed is empty');
        }
    }

    public function refreshFeed(): void
    {
        try {
            if ($this->selectedProfileId) {
                $profile = InstagramProfile::findOrFail($this->selectedProfileId);
                $this->instagramFeed->refreshFeed($profile);
                $this->feed = null; // Reset feed to trigger re-initialization
                $this->refreshAuthStatus();
                Notification::make()
                    ->title('Feed refreshed successfully')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->logError('Error in refreshFeed', [
                'error' => $e->getMessage(),
                'profile_id' => $this->selectedProfileId,
            ]);
    
            $profile = InstagramProfile::findOrFail($this->selectedProfileId);
            
            if (str_contains($e->getMessage(), 'Error validating access token') || 
                str_contains($e->getMessage(), 'Invalid OAuth access token')) {
                $profile->is_authorized = false;
                $profile->access_token = null;
                $profile->token_expires_at = null;
                $profile->save();
    
                $this->logInfo('Profile authorization revoked due to invalid token', [
                    'profile_id' => $this->selectedProfileId,
                ]);
    
                $this->refreshAuthStatus();
    
                Notification::make()
                    ->title('Authorization Required')
                    ->body('Your Instagram profile needs to be reauthorized. Please click the "Authorize with Instagram" button.')
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Failed to refresh feed')
                    ->body('Error: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    public function addNewProfile(): void
    {
        $authUrl = $this->instagramApi->getAuthUrl();
        $this->redirect($authUrl);
    }

    public function clearFeed(): void
    {
        try {
            if ($this->selectedProfileId) {
                $profile = InstagramProfile::find($this->selectedProfileId);
                $this->instagramFeed->clearAllPosts($profile);
                $this->initializeFeed();
                $this->currentPage = 1;
                $this->logInfo('Feed cleared successfully', [
                    'profile_id' => $profile->id
                ]);
                Notification::make()
                    ->title('Feed cleared successfully')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->logError('Error clearing feed', [
                'error' => $e->getMessage(),
                'profile_id' => $this->selectedProfileId
            ]);
            Notification::make()
                ->title('Failed to clear feed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function refreshAuthStatus(): void
    {
        if ($this->selectedProfileId) {
            $profile = InstagramProfile::find($this->selectedProfileId);
            $this->isAuthorized = $profile && $profile->is_authorized && $profile->token_expires_at > now();
            $this->authUrl = $this->instagramFeed->getAuthUrl();
            $this->logInfo('Auth status refreshed', [
                'profile_id' => $profile->id,
                'is_authorized' => $this->isAuthorized,
                'token_expires_at' => $profile->token_expires_at,
            ]);
        } else {
            $this->isAuthorized = false;
            $this->authUrl = $this->instagramFeed->getAuthUrl();
            $this->logInfo('No profile selected, auth status set to false');
        }
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
}