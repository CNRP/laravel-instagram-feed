<x-filament-panels::page>
    <form wire:submit="selectProfile" class="space-y-6">
        {{ $this->form }}
    </form>

    @if($selectedProfileId)
        @if(!$isAuthorized)
            <div class="auth-required">
                <h2>Authorization Required</h2>
                <p>
                    The selected Instagram profile is not authorized. Click the button below to authorize this application with Instagram:
                </p>
                <x-filament::button tag="a" href="{{ $authUrl }}" target="_blank" class="auth-button">
                    Authorize with Instagram
                </x-filament::button>
            </div>
        @endif

        <div class="insta-manager-container">
            <h2>Instagram Feed for {{ $this->profiles->find($selectedProfileId)->username }}</h2>
            <div class="instagram-feed-grid">
                @foreach($this->getFeed() as $post)
                    <div class="instagram-post">
                        @if(!empty($post['media']))
                            <div class="instagram-media-wrapper">
                                <div x-data="{ currentSlide: 0 }" class="instagram-post-media">
                                    <div class="instagram-carousel" x-ref="carousel">
                                        @foreach($post['media'] as $index => $media)
                                            <div class="carousel-item">
                                                @if(strtolower($media['media_type']) === 'image')
                                                    <img src="{{ asset($media['url']) }}" alt="{{ $post['caption'] }}">
                                                @elseif(strtolower($media['media_type']) === 'video')
                                                    <video src="{{ asset($media['url']) }}" poster="{{ asset($media['thumbnail_url']) }}" controls></video>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($post->media->count() > 1)
                                        <button @click="currentSlide = (currentSlide - 1 + {{ count($post['media']) }}) % {{ count($post['media']) }}; $refs.carousel.style.transform = `translateX(-${currentSlide * 100}%)`" class="carousel-button prev">
                                            <svg class="" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l4-4m-4 4 4 4"/>
                                            </svg>
                                        </button>
                                        <button @click="currentSlide = (currentSlide + 1) % {{ count($post['media']) }}; $refs.carousel.style.transform = `translateX(-${currentSlide * 100}%)`" class="carousel-button next">
                                            <svg class="" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/>
                                            </svg>
                                        </button>
                                        <div class="carousel-indicators">
                                            @foreach($post['media'] as $index => $media)
                                                <span class="indicator" :class="{ 'active': currentSlide === {{ $index }} }"></span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                @if(isset($post['edit_url']))
                                    <a href="{{ $post['edit_url'] }}" class="edit-button">
                                        <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z" clip-rule="evenodd"/>
                                            <path fill-rule="evenodd" d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z" clip-rule="evenodd"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        @endif
                        <div class="instagram-post-content">
                            <p class="instagram-post-caption">{{ $post['caption'] }}</p>
                            <a href="{{ $post['permalink'] }}" target="_blank" class="instagram-post-link">View on Instagram</a>
                            <p class="instagram-post-meta">{{ \Carbon\Carbon::parse($post['timestamp'])->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing {{ $this->getFeed()->firstItem() }} to {{ $this->getFeed()->lastItem() }} of {{ $this->getFeed()->total() }} results
                </div>
                <div class="pagination-controls">
                    <x-filament::button
                        wire:click="previousPage"
                        :disabled="$this->getFeed()->onFirstPage()"
                    >
                        Previous
                    </x-filament::button>
                    <span class="page-info">
                        Page {{ $this->getFeed()->currentPage() }} of {{ $this->getFeed()->lastPage() }}
                    </span>
                    <x-filament::button
                        wire:click="nextPage"
                        :disabled="$this->getFeed()->onLastPage()"
                    >
                        Next
                    </x-filament::button>
                </div>
            </div>
        </div>
    @else
        <div class="no-profile-selected">
            <p>No profile selected. Please select a profile to view its feed.</p>
        </div>
    @endif
</x-filament-panels::page>