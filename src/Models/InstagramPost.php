<?php

namespace CNRP\InstagramFeed\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstagramPost extends Model
{
<<<<<<< HEAD
    protected $table = 'cnrp_instagram_posts';

=======
>>>>>>> 8a2f34e73db5af9bcfb0f1741c842c4138c5ab8e
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
