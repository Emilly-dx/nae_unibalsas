<?php

use App\Models\ACL\Permission;
use App\Models\ACL\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Cria um usuário cujo perfil (Role) possui exatamente as permissões informadas.
 */
function userWithPermissions(string ...$slugs): User
{
    $role = Role::factory()->create();

    foreach ($slugs as $slug) {
        $permission = Permission::query()->firstWhere('slug', $slug)
            ?? Permission::factory()->create(['slug' => $slug]);

        $role->permissions()->attach($permission);
    }

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function administrator(): User
{
    $role = Role::query()->firstWhere('slug', 'administrator')
        ?? Role::factory()->create(['slug' => 'administrator']);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}
