<?php

use App\Models\ACL\Module;
use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

test('a module has many permissions', function () {
    $module = Module::factory()->create();
    $permission = Permission::factory()->for($module)->create();

    expect($module->permissions)->toHaveCount(1);
    expect($module->permissions->first()->is($permission))->toBeTrue();
});

test('a permission belongs to a module and can belong to many roles', function () {
    $permission = Permission::factory()->create();
    $role = Role::factory()->create();

    $role->permissions()->attach($permission);

    expect($permission->module)->toBeInstanceOf(Module::class);
    expect($permission->roles->first()->is($role))->toBeTrue();
});

test('a role can belong to many users', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create();

    $user->assignRole($role);

    expect($role->users->first()->is($user))->toBeTrue();
    expect($user->roles->first()->is($role))->toBeTrue();
});

test('syncing role permissions clears the permissions cache of every user with that role', function () {
    $role = Role::factory()->create();
    $permission = Permission::factory()->create();
    $user = User::factory()->create();
    $user->assignRole($role);

    // warm the cache
    $user->allPermissions();
    expect(Cache::has("permissions.user.{$user->id}"))->toBeTrue();

    $role->syncPermissions([$permission->id]);

    expect(Cache::has("permissions.user.{$user->id}"))->toBeFalse();
    expect($user->hasPermissionTo($permission->slug))->toBeTrue();
});

test('deleting a role clears the permissions cache of every user that had it', function () {
    $role = Role::factory()->create();
    $user = User::factory()->create();
    $user->assignRole($role);

    $user->allPermissions();
    expect(Cache::has("permissions.user.{$user->id}"))->toBeTrue();

    $role->delete();

    expect(Cache::has("permissions.user.{$user->id}"))->toBeFalse();
    expect($user->fresh()->hasRole($role->slug))->toBeFalse();
});
