<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

/**
 * Prepara um servidor de testes sem precisar de terminal: perfis e permissões,
 * o primeiro administrador e (opcional) os dados fictícios de demonstração.
 *
 * Pode rodar a cada deploy (ex.: no início do contêiner, docker/start.sh): só cria
 * o que ainda não existe e nunca altera contas ou dados já cadastrados.
 */
class PrepareServer extends Command
{
    protected $signature = 'nae:preparar-servidor';

    protected $description = 'Perfis de acesso, primeiro administrador e dados de demonstração (servidor de testes)';

    private const string DEMO_MARKER = 'psicopedagoga@nae.test';

    public function handle(): int
    {
        $this->callSilently('db:seed', ['--class' => AccessControlSeeder::class, '--force' => true]);
        $this->components->info('Perfis e permissões conferidos.');

        if ($this->createAdministrator() === self::FAILURE) {
            return self::FAILURE;
        }

        return $this->seedDemoData();
    }

    private function createAdministrator(): int
    {
        $email = $this->setting('admin_email');
        $password = $this->setting('admin_password');

        if ($email === null || $password === null) {
            $this->components->warn('NAE_ADMIN_EMAIL/NAE_ADMIN_PASSWORD não definidos: nenhum administrador criado.');

            return self::SUCCESS;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->components->info("Administrador {$email} já existe.");

            return self::SUCCESS;
        }

        return $this->call('nae:criar-admin', [
            '--name' => $this->setting('admin_name') ?? 'Administração do NAE',
            '--email' => $email,
            '--password' => $password,
        ]);
    }

    private function seedDemoData(): int
    {
        if (config('nae.server_setup.demo_data') !== true) {
            return self::SUCCESS;
        }

        if (User::query()->where('email', self::DEMO_MARKER)->exists()) {
            $this->components->info('Dados de demonstração já existem.');

            return self::SUCCESS;
        }

        if (app()->isProduction()) {
            $this->components->error('Dados de demonstração não podem ser criados com APP_ENV=production.');

            return self::FAILURE;
        }

        if (config('nae.demo_password') === 'password' && ! app()->environment(['local', 'testing'])) {
            $this->components->error('Defina uma senha forte em NAE_DEMO_PASSWORD antes de criar os dados de demonstração.');

            return self::FAILURE;
        }

        $this->callSilently('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);
        $this->components->info('Dados fictícios de demonstração criados.');

        return self::SUCCESS;
    }

    private function setting(string $key): ?string
    {
        $value = config("nae.server_setup.{$key}");

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
