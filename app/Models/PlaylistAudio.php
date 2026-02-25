<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistAudio extends Model
{
    protected $fillable = [
        'playlist_id', 
        'session_audio_id', 
        'order', 
        'is_active', 
        'is_public'
    ];
    // Define the relationship with the Playlist model
    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    // Define the relationship with the SessionAudio model
    public function sessionAudio()
    {
        return $this->belongsTo(SessionAudio::class, 'session_audio_id','id');
    }
}
