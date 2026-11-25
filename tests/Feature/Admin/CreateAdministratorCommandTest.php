<?php

use App\Models\User;

test('nae:criar-admin cria um administrador com e-mail verificado', function () {
    $this->artisan('nae:criar-admin', ['--name' => 'Coordenação NAE', '--email' => 'admin@nae.test'])
        ->expectsQuestion('Senha (mínimo 8 caracteres)', 'uma-senha-forte')
        ->assertSuccessful();

    $user = User::query()->where('email', 'admin@nae.test')->sole();
    expect($user->hasRole('administrator'))->toBeTrue()->and($user->email_verified_at)->not->toBeNull();
});

test('nae:criar-admin recusa e-mail repetido', function () {
    User::factory()->create(['email' => 'admin@nae.test']);

    $this->artisan('nae:criar-admin', ['--name' => 'X', '--email' => 'admin@nae.test'])
        ->expectsQuestion('Senha (mínimo 8 caracteres)', 'uma-senha-forte')
        ->assertFailed();
});
