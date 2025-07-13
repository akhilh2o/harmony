<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<\Database\Factories\PageFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'slug',
        'content',
        'status',
        'feature_image', // Add feature_image to fillable properties
    ];


    // Generate slug if not provided
    public static function boot()
    {
        parent::boot();

        static::saving(function ($page) {
            if (!$page->slug) {
                $page->slug = \Illuminate\Support\Str::slug($page->title);
            }
        });
    }
}
