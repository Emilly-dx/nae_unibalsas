<?php

namespace App\Models\ACL;

use App\Models\User;
use Database\Factories\ACL\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'slug'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * @param  array<int>  $permissionIds
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);

        $this->clearUsersPermissionsCache();
    }

    public function clearUsersPermissionsCache(): void
    {
        $this->users()->pluck('users.id')->each(
            fn (int $userId) => Cache::forget("permissions.user.{$userId}")
        );
    }

    protected static function booted(): void
    {
        // Captured before delete: the role_user pivot cascades away with the role.
        static::deleting(fn (Role $role) => $role->clearUsersPermissionsCache());
    }
}
