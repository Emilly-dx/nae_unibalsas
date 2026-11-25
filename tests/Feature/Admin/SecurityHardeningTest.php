<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

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

test('erros 403 e 404 aparecem em português, no visual do sistema', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.audit.index'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('ErrorPage')->where('status', 403));

    $this->get('/pagina-que-nao-existe')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('ErrorPage')->where('status', 404));
});

test('formulário expirado (419) volta para a página com aviso, sem tela de erro', function () {
    Route::middleware('web')->post('/_teste-419', fn () => abort(419));

    $this->actingAs(User::factory()->create())
        ->from(route('dashboard'))
        ->post('/_teste-419')
        ->assertRedirect(route('dashboard'));
});
