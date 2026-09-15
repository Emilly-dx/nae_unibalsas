<?php

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

test('assignRole attaches a role by model or by slug', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['slug' => 'assistant']);

    $user->assignRole($role);
    expect($user->hasRole('assistant'))->toBeTrue();

    $user->syncRoles([]);
    $user->assignRole('assistant');
    expect($user->fresh()->hasRole('assistant'))->toBeTrue();
});

test('assignRole never detaches roles the user already has', function () {
    $user = User::factory()->create();
    $roleA = Role::factory()->create();
    $roleB = Role::factory()->create();

    $user->assignRole($roleA);
    $user->assignRole($roleB);

    expect($user->fresh()->roles)->toHaveCount(2);
});

test('syncRoles replaces the user roles entirely', function () {
    $user = User::factory()->create();
    $roleA = Role::factory()->create();
    $roleB = Role::factory()->create();

    $user->assignRole($roleA);
    $user->syncRoles([$roleB->slug]);

    $user->refresh();
    expect($user->hasRole($roleA->slug))->toBeFalse();
    expect($user->hasRole($roleB->slug))->toBeTrue();
});

test('hasRole accepts a single slug or a list of slugs', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['slug' => 'psychologist']);
    $user->assignRole($role);

    expect($user->hasRole('psychologist'))->toBeTrue();
    expect($user->hasRole(['coordinator', 'psychologist']))->toBeTrue();
    expect($user->hasRole('coordinator'))->toBeFalse();
});

test('allPermissions aggregates unique permissions across all of the user roles', function () {
    $user = User::factory()->create();
    $shared = Permission::factory()->create();
    $roleA = Role::factory()->create();
    $roleB = Role::factory()->create();
    $roleA->permissions()->attach($shared);
    $roleB->permissions()->attach($shared);

    $user->assignRole($roleA);
    $user->assignRole($roleB);

    expect($user->allPermissions())->toHaveCount(1);
    expect($user->permissionSlugs()->all())->toBe([$shared->slug]);
});

test('hasPermissionTo and hasPermission agree on the same result', function () {
    $user = User::factory()->create();
    $permission = Permission::factory()->create();
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);
    $user->assignRole($role);

    expect($user->hasPermissionTo($permission->slug))->toBeTrue();
    expect($user->hasPermission($permission->slug))->toBeTrue();
    expect($user->hasPermission('nonexistent.slug'))->toBeFalse();
});

test('permissions are cached under permissions.user.{id} and cleared explicitly', function () {
    $user = User::factory()->create();
    $key = "permissions.user.{$user->id}";

    expect(Cache::has($key))->toBeFalse();

    $user->allPermissions();
    expect(Cache::has($key))->toBeTrue();

    $user->clearPermissionsCache();
    expect(Cache::has($key))->toBeFalse();
});

test('avatarUrl returns null without an avatar and a storage url with one', function () {
    Storage::fake('public');

    $user = User::factory()->create(['avatar' => null]);
    expect($user->avatarUrl())->toBeNull();

    $user->avatar = 'avatars/user.png';
    expect($user->avatarUrl())->toBe(Storage::disk('public')->url('avatars/user.png'));
});
