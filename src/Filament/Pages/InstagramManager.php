<?php

namespace CNRP\InstagramFeed\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Dymantic\InstagramFeed\Profile;
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
        // $this->refreshAuthStatus();
        // $this->refreshFeed();
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
            $profile = Profile::for($this->profileName);
    
            if ($profile === null) {
                throw new \Exception('Profile is null');
            }
    
            $feedData = $profile->feed();
            
            // Debugging: Log the raw feed data
            Log::info('Raw feed data:', (array)$feedData);
    
            $this->feed = $this->transformFeedData($feedData);
    
            // Debugging: Log the transformed feed data
            Log::info('Transformed feed data:', $this->feed);
    
            Notification::make()
                ->title('Feed refreshed successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('Error refreshing feed:', ['error' => $e->getMessage()]);
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
        $profile = Profile::for($this->profileName);

        if ($profile === null) {
            Log::error('Profile is null for profile name:', ['profileName' => $this->profileName]);
            $this->isAuthorized = false;
            $this->authUrl = null;
            return;
        }

        $this->isAuthorized = $profile->hasInstagramAccess();
        $this->authUrl = $profile->getInstagramAuthUrl();
    }
    
    protected function transformFeedData($feedData): array
    {
        $reflection = new \ReflectionClass($feedData);
        $itemsProperty = $reflection->getProperty('items');
        $itemsProperty->setAccessible(true);
        $items = $itemsProperty->getValue($feedData);
    
        if (empty($items)) {
            Log::error('No items found in feed data');
            return [];
        }
    
        return array_map(function ($post) {
            return [
                'id' => $post->id ?? '',
                'url' => $post->url ?? '',
                'caption' => $post->caption ?? '',
                'permalink' => $post->permalink ?? '',
                'timestamp' => $post->timestamp ?? '',
                'type' => $post->type ?? '',
                // Add any other fields you need
            ];
        }, $items);
    }
    
    
    
}
