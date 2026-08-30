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
        $spatieRoles = $this->getRoleNames();
        $staffRole = $spatieRoles->first(fn($r) => !in_array($r, ['admin', 'customer', 'seller']));
        $roleSlug = $staffRole ?: ($spatieRoles->first() ?: ($this->role?->value ?? $this->role));
        $isSuperAdmin = $this->hasRoleSafely('super_admin') || ($this->id === 1 && ($this->role?->value ?? $this->role) === 'admin');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value ?? $this->role,
            'role_label' => $this->role?->label(),
            'role_slug' => $roleSlug,
            'roles' => $spatieRoles->values(),
            'is_super_admin' => $isSuperAdmin,
            'status' => $this->status?->value ?? $this->status,
            'email_verified' => $this->email_verified_at !== null,
            'phone_verified' => $this->phone_verified_at !== null,
            'permissions' => $isSuperAdmin ? ['*'] : $this->getAllPermissions()->pluck('name')->values(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
