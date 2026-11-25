<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AccessControlSeeder::class);

        // Conta com senha conhecida só no computador local. Online (produção ou
        // versão de testes), o administrador é criado com `php artisan nae:criar-admin`.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $user = User::factory()->create([
            'name' => 'Administrador (teste)',
            'email' => 'test@example.com',
        ]);

        $user->assignRole('administrator');
    }
}
