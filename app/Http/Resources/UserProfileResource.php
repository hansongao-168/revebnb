<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
            'gender' => (int) $this->gender,
            'status' => (int) $this->status,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
        ];
    }
}
