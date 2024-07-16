<?php

namespace CNRP\InstagramFeed\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramMedia extends Model
{
    protected $fillable = [
        'instagram_post_id',
        'media_type',
        'url',
        'thumbnail_url',
    ];

    public function post()
    {
        return $this->belongsTo(InstagramPost::class, 'instagram_post_id');
    }
}