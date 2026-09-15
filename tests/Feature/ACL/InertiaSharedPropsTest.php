<?php

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('auth.permissions and auth.roles are shared with every inertia response', function () {
    $permission = Permission::factory()->create(['slug' => 'students.view']);
    $role = Role::factory()->create(['slug' => 'psychopedagogue']);
    $role->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', ['students.view'])
            ->where('auth.roles', ['psychopedagogue'])
        );
});

test('auth.permissions and auth.roles are empty for a guest', function () {
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', [])
            ->where('auth.roles', [])
        );
});
