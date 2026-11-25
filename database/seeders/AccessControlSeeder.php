<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Módulos, permissões, perfis e a proposta de permissões por perfil.
 * Pode ser executado de novo sem problema (só acrescenta o que falta):
 *
 *   php artisan db:seed --class=AccessControlSeeder
 */
class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        // As permissões de cada usuário ficam em cache: recalcula na próxima requisição.
        User::query()->each(fn (User $user) => $user->clearPermissionsCache());
    }
}
