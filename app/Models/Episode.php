<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Episode extends Model
{
    protected $fillable = [
        'podcast_id',
        'title',
        'description',
        'audio_url',
        'guid',
        'thumbnail_url',
        'duration_seconds',
        'duration_formatted',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function podcast(): BelongsTo
    {
        return $this->belongsTo(Podcast::class);
    }

    public function userEpisodes(): HasMany
    {
        return $this->hasMany(UserEpisode::class);
    }

    public function getShortDescriptionAttribute(): string
    {
        if (!$this->description) {
            return '';
        }
        
        return strlen($this->description) > 100 
            ? substr($this->description, 0, 100) . '...' 
            : $this->description;
    }
}
