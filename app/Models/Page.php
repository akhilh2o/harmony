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

    public $statusOptions = ['draft', 'published', 'archived', 'deleted'];

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }


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
