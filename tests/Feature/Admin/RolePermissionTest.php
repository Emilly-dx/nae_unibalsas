<?php

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(AccessControlSeeder::class);
});

test('mostra os perfis com as permissões agrupadas por módulo', function () {
    $this->actingAs(administrator())->get(route('admin.roles.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Roles')
            ->has('roles', 6)
            ->where('roles.0.slug', 'administrator')
            ->where('roles.0.locked', true)
            ->has('modules', 9)
            ->where('canManage', true)
        );
});

test('altera as permissões de um perfil e vale na hora para quem tem o perfil', function () {
    $role = Role::query()->where('slug', 'assistant')->sole();
    $user = User::factory()->create();
    $user->assignRole($role);
    expect($user->hasPermissionTo('reports.view'))->toBeFalse();

    $ids = [...$role->permissions()->pluck('permissions.id')->all(), Permission::query()->where('slug', 'reports.view')->value('id')];

    $this->actingAs(administrator())
        ->put(route('admin.roles.update', $role), ['permissions' => $ids])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasPermissionTo('reports.view'))->toBeTrue();

    $log = Activity::query()->where('event', 'permissions_updated')->sole();
    expect($log->properties->get('added'))->toBe(['reports.view']);
});

test('o perfil Administrador não é editável', function () {
    $role = Role::query()->where('slug', 'administrator')->sole();

    $this->actingAs(administrator())->put(route('admin.roles.update', $role), ['permissions' => []])->assertForbidden();
});

test('exige acl.manage_permissions', function () {
    $role = Role::query()->where('slug', 'assistant')->sole();
    $user = userWithPermissions('users.view', 'acl.assign_roles');

    $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    $this->actingAs($user)->put(route('admin.roles.update', $role), ['permissions' => []])->assertForbidden();
});
