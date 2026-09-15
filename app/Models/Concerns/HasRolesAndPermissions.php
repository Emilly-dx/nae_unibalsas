<?php

namespace App\Models\Concerns;

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 */
trait HasRolesAndPermissions
{
    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        return Cache::rememberForever(
            $this->permissionsCacheKey(),
            fn () => $this->roles()
                ->with('permissions')
                ->get()
                ->flatMap(fn (Role $role) => $role->permissions)
                ->unique('id')
                ->values(),
        );
    }

    /**
     * @return Collection<int, string>
     */
    public function permissionSlugs(): Collection
    {
        return $this->allPermissions()->pluck('slug');
    }

    public function hasPermissionTo(string $slug): bool
    {
        return $this->permissionSlugs()->contains($slug);
    }

    public function hasPermission(string $slug): bool
    {
        return $this->hasPermissionTo($slug);
    }

    /**
     * @param  string|array<int, string>  $slug
     */
    public function hasRole(string|array $slug): bool
    {
        return $this->roles->pluck('slug')->intersect((array) $slug)->isNotEmpty();
    }

    public function assignRole(Role|string $role): void
    {
        $role = $role instanceof Role ? $role : Role::where('slug', $role)->firstOrFail();

        $this->roles()->syncWithoutDetaching($role);

        $this->clearPermissionsCache();
    }

    /**
     * @param  array<Role|string>  $roles
     */
    public function syncRoles(array $roles): void
    {
        $ids = collect($roles)->map(
            fn (Role|string $role) => $role instanceof Role ? $role->id : Role::where('slug', $role)->firstOrFail()->id,
        );

        $this->roles()->sync($ids);

        $this->clearPermissionsCache();
    }

    public function clearPermissionsCache(): void
    {
        Cache::forget($this->permissionsCacheKey());
    }

    public function avatarUrl(): ?string
    {
        /** @var string|null $avatar */
        $avatar = $this->avatar;

        return $avatar ? Storage::disk('public')->url($avatar) : null;
    }

    protected function permissionsCacheKey(): string
    {
        return "permissions.user.{$this->id}";
    }
}
