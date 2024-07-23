<?php

namespace CNRP\InstagramFeed\Filament\Resources\InstagramPostResource\Pages;

use CNRP\InstagramFeed\Filament\Resources\InstagramPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstagramPost extends CreateRecord
{
    protected static string $resource = InstagramPostResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}