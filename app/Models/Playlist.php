<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    protected $fillable = ['name', 'description', 'user_id', 'is_active', 'is_public'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function audios()
    {
        return $this->hasMany(PlaylistAudio::class);
    }
}
