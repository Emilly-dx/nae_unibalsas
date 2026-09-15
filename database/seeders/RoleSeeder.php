<?php

namespace Database\Seeders;

use App\Models\ACL\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Initial roles (SRS §7.7). Permission-to-role assignment is not seeded
     * here: it's an open item pending validation with the NAE (SRS §28.11).
     * `administrator` needs none — it bypasses every check via Gate::before.
     */
    public function run(): void
    {
        $roles = [
            ['slug' => 'administrator', 'name' => 'Administrador'],
            ['slug' => 'coordinator', 'name' => 'Coordenador'],
            ['slug' => 'psychopedagogue', 'name' => 'Psicopedagogo'],
            ['slug' => 'psychologist', 'name' => 'Psicólogo'],
            ['slug' => 'pedagogue', 'name' => 'Pedagogo'],
            ['slug' => 'assistant', 'name' => 'Assistente'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
