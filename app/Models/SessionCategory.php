<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionCategory extends Model
{
    protected $guarded = [];


    public function sessions()
    {
        return $this->hasMany(SessionAudio::class);
    }
}
