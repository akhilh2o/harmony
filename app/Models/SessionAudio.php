<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAudio extends Model
{
    protected $guarded = [];
    protected $table = 'session_audio';

    protected $casts = [
        'is_active' => 'boolean',
        'is_free'   => 'boolean',
    ];

    public function session_category()
    {
        return $this->belongsTo(SessionCategory::class);
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class, 'playlist_audio', 'session_audio_id', 'playlist_id')
                    ->withPivot('order', 'is_active');
    }
}
