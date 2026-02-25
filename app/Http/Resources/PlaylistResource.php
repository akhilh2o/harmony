<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
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
            'description' => $this->description,
            'user_id' => $this->user_id,
            'is_active' => $this->is_active ? true : false,
            'user' => new UserResource($this->whenLoaded('user')),
            'audios' => SessionAudioResource::collection($this->whenLoaded('audios')),
            'is_public' => $this->is_public ? true : false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
