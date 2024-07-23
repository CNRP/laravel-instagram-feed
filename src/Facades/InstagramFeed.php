<?php

namespace CNRP\InstagramFeed\Facades;

use Illuminate\Support\Facades\Facade;

class InstagramFeed extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'instagram-feed';
    }
}
