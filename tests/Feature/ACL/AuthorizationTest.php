<?php

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('an administrator is granted any ability, even one that was never defined', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::factory()->create(['slug' => 'administrator']));

    expect(Gate::forUser($admin)->allows('students.delete'))->toBeTrue();
});

test('a user is granted an ability only when they hold the matching permission', function () {
    $permission = Permission::factory()->create(['slug' => 'students.view']);
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect(Gate::forUser($user)->allows('students.view'))->toBeTrue();
    expect(Gate::forUser($user)->allows('students.delete'))->toBeFalse();
});

test('a user without any role is denied every ability', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('students.view'))->toBeFalse();
});
