<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionAudioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'file'  => $this->file ? asset('storage/' . $this->file) : null,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'duration' => $this->duration,
            'color' => $this->color,
            'is_active' => $this->is_active,
            'is_free' => $this->is_free,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
