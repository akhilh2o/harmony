<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Playlist extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'user_id',
        'is_active',
        'is_public',
        'is_free',
        'color',
        'image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_free'   => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($playlist) {
            $playlist->slug = Str::slug($playlist->name) . '-' . Str::random(6);
        });

        static::updating(function ($playlist) {
            if ($playlist->isDirty('name')) {
                $playlist->slug = Str::slug($playlist->name) . '-' . Str::random(6);
            }
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function audios()
    {
        return $this->hasMany(PlaylistAudio::class)->orderBy('order');
    }

    public function sessionAudios()
    {
        return $this->belongsToMany(SessionAudio::class, 'playlist_audio', 'playlist_id', 'session_audio_id')
                    ->withPivot('order', 'is_active')
                    ->orderBy('playlist_audio.order');
    }
}
