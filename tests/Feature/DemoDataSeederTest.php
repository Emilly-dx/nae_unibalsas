<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Hash;

test('os dados de demonstração são criados com uma conta para cada perfil', function () {
    $this->seed(DemoDataSeeder::class);

    expect(Student::query()->count())->toBe(18)
        ->and(Attendance::query()->count())->toBeGreaterThan(10)
        ->and(User::query()->where('email', 'psicopedagoga@nae.test')->sole()->hasRole('psychopedagogue'))->toBeTrue();
});

test('fora do computador local exige uma senha forte para as contas de demonstração', function () {
    app()->detectEnvironment(fn () => 'staging');

    expect(fn () => $this->seed(DemoDataSeeder::class))->toThrow(RuntimeException::class);

    config(['nae.demo_password' => 'Demo-Senha-Forte-2026']);
    $this->seed(DemoDataSeeder::class);

    expect(Hash::check('Demo-Senha-Forte-2026', User::query()->where('email', 'coordenacao@nae.test')->sole()->password))->toBeTrue();
});

test('não roda em produção', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(DemoDataSeeder::class)->run())->toThrow(RuntimeException::class, 'produção');
});
