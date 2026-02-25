<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionAudio extends Model
{
    protected $guarded = [];

    protected $table = 'session_audio';

    public function session_category()
    {
        return $this->belongsTo(SessionCategory::class);
    }
}
