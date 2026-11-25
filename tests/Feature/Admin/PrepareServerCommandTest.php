<?php

use App\Models\Student;
use App\Models\User;

test('nae:preparar-servidor cria o administrador a partir das variáveis', function () {
    config(['nae.server_setup.admin_email' => 'admin@nae.test', 'nae.server_setup.admin_password' => 'uma-senha-forte']);

    $this->artisan('nae:preparar-servidor')->assertSuccessful();

    expect(User::query()->where('email', 'admin@nae.test')->sole()->hasRole('administrator'))->toBeTrue();
});

test('nae:preparar-servidor pode rodar a cada deploy sem duplicar nada', function () {
    config([
        'nae.server_setup.admin_email' => 'admin@nae.test',
        'nae.server_setup.admin_password' => 'uma-senha-forte',
        'nae.server_setup.demo_data' => true,
    ]);

    $this->artisan('nae:preparar-servidor')->assertSuccessful();
    $users = User::query()->count();
    $students = Student::query()->count();

    $this->artisan('nae:preparar-servidor')->assertSuccessful();

    expect(User::query()->count())->toBe($users)
        ->and(Student::query()->count())->toBe($students)
        ->and($students)->toBeGreaterThan(0);
});

test('nae:preparar-servidor não cria administrador sem as variáveis nem dados de demonstração sem pedir', function () {
    $this->artisan('nae:preparar-servidor')->assertSuccessful();

    expect(User::query()->count())->toBe(0)->and(Student::query()->count())->toBe(0);
});

test('nae:preparar-servidor exige senha forte para os dados de demonstração fora do computador local', function () {
    app()->detectEnvironment(fn () => 'staging');
    config(['nae.server_setup.demo_data' => true, 'nae.demo_password' => 'password']);

    $this->artisan('nae:preparar-servidor')->assertFailed();

    expect(Student::query()->count())->toBe(0);
});
