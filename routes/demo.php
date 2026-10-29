<?php

use Illuminate\Support\Facades\Route;

/*
| Protótipo navegável (dados fictícios) dos módulos ainda não implementados,
| para validação com a equipe do NAE: M4 (Triagem + Atendimentos),
| M6 (Encaminhamentos) e M8 (Relatórios). As páginas não leem nem gravam
| dados; serão substituídas pelas implementações reais de cada marco.
| Para desligar: NAE_DEMO_SCREENS=false no .env.
*/
if (config('nae.demo_screens')) {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::inertia('triagem', 'demo/Triage')->name('demo.triage');
        Route::inertia('triagem/nova-solicitacao', 'demo/NewRequest')->name('demo.triage.create');
        Route::inertia('atendimentos/registrar', 'demo/RegisterAttendance')->name('demo.attendances.create');
        Route::inertia('encaminhamentos', 'demo/Referrals')->name('demo.referrals');
        Route::inertia('relatorios', 'demo/Reports')->name('demo.reports');
    });
}
