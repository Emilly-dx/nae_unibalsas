<?php

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/*
 * Os testes rodam com CACHE_STORE=array, que não serializa nada. Em
 * desenvolvimento/produção o projeto usa o Database Cache (RNF-017), que
 * serializa os valores e, com `cache.serializable_classes = false`, não
 * reconstrói objetos — models em cache voltavam como __PHP_Incomplete_Class.
 */
test('permissões em cache funcionam com o Database Cache (serialização real)', function () {
    config(['cache.default' => 'database']);
    Cache::setDefaultDriver('database');

    $permission = Permission::factory()->create(['slug' => 'students.view']);
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    // 1ª chamada grava no cache; a 2ª (nova instância) lê do banco de cache.
    $user->allPermissions();
    $fresh = User::query()->findOrFail($user->id);

    expect($fresh->permissionSlugs()->all())->toBe(['students.view'])
        ->and($fresh->allPermissions()->first())->toBeInstanceOf(Permission::class)
        ->and($fresh->hasPermissionTo('students.view'))->toBeTrue();
});
