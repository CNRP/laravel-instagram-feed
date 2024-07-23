<?php

namespace CNRP\InstagramFeed\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramProfile extends Model
{
    protected $table = 'cnrp_instagram_profiles';

    protected $fillable = [
        'username',
        'user_id',
        'access_token',
        'token_expires_at',
        'user_fullname',
        'user_profile_picture',
        'is_authorized',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_authorized' => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(InstagramPost::class, 'instagram_profile_id');
    }
}