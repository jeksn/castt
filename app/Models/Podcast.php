<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Podcast extends Model
{
    protected $fillable = [
        'title',
        'description',
        'rss_url',
        'image_url',
        'author',
        'website_url',
        'last_refreshed_at',
    ];

    protected $casts = [
        'last_refreshed_at' => 'datetime',
    ];

    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class);
    }

    public function latestEpisodes(int $limit = 10): HasMany
    {
        return $this->episodes()->orderBy('published_at', 'desc')->limit($limit);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_podcasts')
                    ->withPivot('subscribed_at')
                    ->withTimestamps();
    }
}
