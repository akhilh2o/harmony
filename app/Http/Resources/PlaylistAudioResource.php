<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistAudioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'order'           => $this->order,
            'is_active'       => (bool) $this->is_active,
            'session_audio'   => new SessionAudioResource($this->whenLoaded('sessionAudio')),
        ];
    }
}
