<?php

namespace CNRP\InstagramFeed\Models;

use Illuminate\Database\Eloquent\Model;

class InstagramMedia extends Model
{
    protected $table = 'cnrp_instagram_media';

    protected $fillable = ['instagram_post_id', 'instagram_media_id', 'url', 'media_type', 'thumbnail_url'];

    public function post()
    {
        return $this->belongsTo(InstagramPost::class, 'instagram_post_id');
    }

}