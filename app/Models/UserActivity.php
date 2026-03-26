<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id', 'session_audio_id', 'action', 'listened_seconds'
    ];

    public function sessionAudio()
    {
        return $this->belongsTo(SessionAudio::class, 'session_audio_id');
    }
}