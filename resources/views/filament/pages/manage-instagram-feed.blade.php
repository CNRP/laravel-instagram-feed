<x-filament-panels::page>
    @if(!$isAuthorized)
            <div class="mt-8">
                <h2 class="text-lg font-medium text-gray-900">Authorization Required</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Your Instagram profile is not authorized. Click the button below to authorize this application with Instagram:
                </p>
                <x-filament::button tag="a" href="{{ $authUrl }}" target="_blank" class="mt-4">
                    Authorize with Instagram
                </x-filament::button>
            </div>
        @else
        <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900">Instagram Feed</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-4 mt-4">
            @foreach($feed as $post)
                <div class="overflow-hidden border rounded-lg">
                    <img src="{{ $post['url'] }}" alt="{{ $post['caption'] }}" class="object-cover w-full h-48">
                    <div class="p-4">
                        <p class="text-sm text-gray-600 truncate">{{ $post['caption'] }}</p>
                        <a href="{{ $post['permalink'] }}" target="_blank" class="text-xs text-blue-500 hover:underline">View on Instagram</a>
                        <p class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($post['timestamp'])->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</x-filament-panels::page>