<?php

namespace CNRP\InstagramFeed\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD

class InstagramMedia extends Model
{
    protected $table = 'cnrp_instagram_media';

    protected $fillable = ['instagram_post_id', 'instagram_media_id', 'url', 'media_type', 'thumbnail_url'];
=======
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramMedia extends Model
{
    protected $fillable = [
        'instagram_post_id',
        'media_type',
        'url',
        'thumbnail_url',
    ];
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e

    public function post()
    {
        return $this->belongsTo(InstagramPost::class, 'instagram_post_id');
    }
<<<<<<< HEAD

=======
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
}