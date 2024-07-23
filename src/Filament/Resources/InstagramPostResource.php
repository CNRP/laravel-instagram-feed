<?php

namespace CNRP\InstagramFeed\Filament\Resources;

use CNRP\InstagramFeed\Models\InstagramPost;
use CNRP\InstagramFeed\Filament\Resources\InstagramPostResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;

class InstagramPostResource extends Resource
{
    protected static ?string $model = InstagramPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-camera';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('instagram_id')
                    ->maxLength(255)
                    ->label('Instagram ID')
                    ->disabled(),
                Forms\Components\TextInput::make('permalink')
                    ->url()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\Select::make('type')
                    ->options([
                        'IMAGE' => 'Image',
                        'CAROUSEL_ALBUM' => 'Carousel',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('timestamp')
                    ->required(),
                Forms\Components\Textarea::make('caption')
                    ->maxLength(2200)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_hidden')
                    ->label('Hide from feed'),
                Repeater::make('media')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('media_type')
                            ->options([
                                'IMAGE' => 'Image',
                                'VIDEO' => 'Video',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('instagram_media_id')
                            ->maxLength(2200)
                            ->columnSpanFull(),
                        FileUpload::make('url')
                            ->image()
                            ->disk('instagram')
                            // ->directory('images/instagram/instagram_18024350173455893.webp')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['image/webp', 'image/jpeg', 'image/png'])
                            ->maxSize(5120) // 5MB
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstagramPosts::route('/'),
            'create' => Pages\CreateInstagramPost::route('/create'),
            'edit' => Pages\EditInstagramPost::route('/{record}/edit'),
        ];
    }
}