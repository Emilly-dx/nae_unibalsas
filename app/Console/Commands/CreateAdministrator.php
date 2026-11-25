<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Cria a primeira conta de Administrador (uso na hospedagem).
 * As demais contas são criadas pela tela Usuários.
 */
class CreateAdministrator extends Command
{
    protected $signature = 'nae:criar-admin {--name=} {--email=} {--password= : Para painéis que não aceitam perguntas (ex.: Commands do Laravel Cloud)}';

    protected $description = 'Cria uma conta de Administrador da plataforma do NAE';

    public function handle(): int
    {
        $this->callSilently('db:seed', ['--class' => AccessControlSeeder::class, '--force' => true]);

        $name = $this->option('name') ?: text('Nome completo', required: true);
        $email = $this->option('email') ?: text('E-mail', required: true);
        $secret = $this->option('password') ?: password('Senha (mínimo 8 caracteres)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $secret],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::defaults()],
            ],
            ['email.unique' => 'Já existe uma conta com este e-mail.'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $secret]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole('administrator');

        $this->components->info("Administrador {$user->email} criado.");

        return self::SUCCESS;
    }
}
