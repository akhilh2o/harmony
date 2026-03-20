<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PlaylistAudioResource;  // ← yeh missing tha

class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'color'        => $this->color,
            'image'        => $this->image ? asset('storage/' . $this->image) : null,
            'is_active'    => (bool) $this->is_active,
            'is_public'    => (bool) $this->is_public,
            'is_free'      => (bool) $this->is_free,
            'user_id'      => $this->user_id,
            'audios_count' => $this->whenCounted('audios'),
            'audios'       => PlaylistAudioResource::collection($this->whenLoaded('audios')),
            'created_at'   => $this->created_at->toDateTimeString(),
            'updated_at'   => $this->updated_at->toDateTimeString(),
        ];
    }
}