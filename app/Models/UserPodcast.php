<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPodcast extends Model
{
    protected $fillable = [
        'user_id',
        'podcast_id',
        'subscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function podcast(): BelongsTo
    {
        return $this->belongsTo(Podcast::class);
    }
}
