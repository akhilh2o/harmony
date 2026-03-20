<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'email'                   => $this->email,
            'phone_code'              => $this->phone_code,
            'phone'                   => $this->phone,
            'avatar'                  => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'is_admin'                => (bool) $this->is_admin,
            'is_active'               => (bool) $this->is_active,
            'is_subscribed'           => (bool) $this->is_subscribed,
            'subscription_plan'       => $this->subscription_plan,
            'subscription_expires_at' => $this->subscription_expires_at?->toDateTimeString(),
            'email_verified_at'       => $this->email_verified_at?->toDateTimeString(),
            'phone_verified_at'       => $this->phone_verified_at?->toDateTimeString(),
            'created_at'              => $this->created_at->toDateTimeString(),
            'updated_at'              => $this->updated_at->toDateTimeString(),
        ];
    }
}
