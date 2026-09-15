<?php

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'permission:students.create'])
        ->get('/__test/students/create', fn () => 'ok');
});

test('a guest is redirected to login', function () {
    $this->get('/__test/students/create')->assertRedirect(route('login'));
});

test('an authenticated user without the permission is forbidden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/__test/students/create')
        ->assertForbidden();
});

test('an authenticated user with the permission is allowed through', function () {
    $permission = Permission::factory()->create(['slug' => 'students.create']);
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get('/__test/students/create')
        ->assertOk()
        ->assertSee('ok');
});

test('an administrator is allowed through regardless of assigned permissions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::factory()->create(['slug' => 'administrator']));

    $this->actingAs($admin)
        ->get('/__test/students/create')
        ->assertOk();
});
