<?php

namespace CNRP\InstagramFeed\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use CNRP\InstagramFeed\Facades\InstagramFeed;
use Illuminate\Support\Facades\Log;

class InstagramManager extends Page 
{
    protected static ?string $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationLabel = 'Instagram Feed';
    protected static ?string $title = 'Manage Instagram Feed';
    protected static ?string $slug = 'instagram-feed';
    protected static string $view = 'instagram-feed::filament.pages.manage-instagram-feed';

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
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshFeed')
                ->label('Refresh Feed')
                ->action('refreshFeed'),
        ];
    }

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Notification::make()
                ->title('Failed to refresh feed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function refreshAuthStatus(): void
    {
        Log::info('Refreshing auth status for profile:', ['profileName' => $this->profileName]);
        try {
            $this->isAuthorized = InstagramFeed::isAuthorized();
            $this->authUrl = InstagramFeed::getAuthUrl();
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