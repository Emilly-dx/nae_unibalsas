<?php

namespace Database\Seeders;

use App\Models\ACL\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Initial modules (SRS §7.5, §7.6).
     */
    public function run(): void
    {
        $modules = [
            ['slug' => 'profiles', 'name' => 'Perfis'],
            ['slug' => 'users', 'name' => 'Usuários'],
            ['slug' => 'acl', 'name' => 'ACL'],
            ['slug' => 'students', 'name' => 'Estudantes'],
            ['slug' => 'attendances', 'name' => 'Atendimentos'],
            ['slug' => 'referrals', 'name' => 'Encaminhamentos'],
            ['slug' => 'reports', 'name' => 'Relatórios'],
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(['slug' => $module['slug']], $module);
        }
    }
}
