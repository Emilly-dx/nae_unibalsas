<?php

use App\Models\User;

test('não há cadastro público: as contas são criadas pela administração', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password', 'password_confirmation' => 'password'])->assertNotFound();
});

test('respostas trazem cabeçalhos de segurança', function () {
    $this->get(route('login'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
});

test('páginas autenticadas não ficam no cache do navegador', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('mensagem de login errado em português e sem revelar se o e-mail existe', function () {
    $this->post(route('login.store'), ['email' => 'ninguem@example.com', 'password' => 'x'])
        ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
});
