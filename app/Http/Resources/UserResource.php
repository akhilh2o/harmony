<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone_code'        => $this->phone_code,
            'phone'             => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'phone_verified_at' => $this->phone_verified_at?->toDateTimeString(),
            'created_at'        => $this->created_at->toDateTimeString(),
        ];
    }
}
