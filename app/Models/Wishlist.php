<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = ['user_id', 'session_audio_id'];

    public function sessionAudio()
    {
        return $this->belongsTo(SessionAudio::class, 'session_audio_id');
    }
}