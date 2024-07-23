<?php

namespace CNRP\InstagramFeed\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramPost extends Model
{
    protected $table = 'cnrp_instagram_posts';

    protected $fillable = [
        'instagram_id',
        'type',
        'caption',
        'permalink',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function media(): HasMany
    {
        return $this->hasMany(InstagramMedia::class);
    }
}
