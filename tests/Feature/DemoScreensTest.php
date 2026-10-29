<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

$screens = [
    '/triagem' => 'demo/Triage',
    '/triagem/nova-solicitacao' => 'demo/NewRequest',
    '/atendimentos/registrar' => 'demo/RegisterAttendance',
    '/encaminhamentos' => 'demo/Referrals',
    '/relatorios' => 'demo/Reports',
];

test('as telas de demonstração exigem login', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with(array_keys($screens));

test('as telas de demonstração abrem para usuários autenticados', function (string $url, string $component) {
    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('demoScreens', true)
        );
})->with(fn () => collect($screens)->map(fn ($component, $url) => [$url, $component])->values()->all());
