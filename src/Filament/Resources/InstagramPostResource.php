<?php

namespace CNRP\InstagramFeed\Filament\Resources;

use CNRP\InstagramFeed\Models\InstagramPost;
use CNRP\InstagramFeed\Filament\Resources\InstagramPostResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;

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

                                Grid::make(3)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('media_type')
                                                    ->options([
                                                        'IMAGE' => 'Image',
                                                        'VIDEO' => 'Video',
                                                    ])
                                                    ->required(),
                                                Forms\Components\TextInput::make('instagram_media_id')
                                                    ->label('Media ID')
                                                    ->maxLength(255),
                                            ]),
                                        FileUpload::make('url')
                                            ->image()
                                            ->disk('instagram')
                                            ->preserveFilenames()
                                            ->acceptedFileTypes(['image/webp', 'image/jpeg', 'image/png'])
                                            ->maxSize(5120) // 5MB
                                            ->required()
                                            ->columnSpan(3),
                                    ])
                    ])
                    ->columnSpanFull()
                    ->grid(3)
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