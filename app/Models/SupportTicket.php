<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = [
        'name', 'email', 'subject', 'description', 'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}