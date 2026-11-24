<?php

use Illuminate\Support\Facades\Route;

/*
| Protótipo navegável (dados fictícios) dos módulos ainda não implementados,
| para validação com a equipe do NAE: M8 (Relatórios).
| As páginas não leem nem gravam dados; serão substituídas pelas
| implementações reais de cada marco. Para desligar: NAE_DEMO_SCREENS=false.
*/
if (config('nae.demo_screens')) {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::inertia('relatorios', 'demo/Reports')->name('demo.reports');
    });
}
