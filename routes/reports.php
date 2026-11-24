<?php

use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

/*
| M8 — Relatórios + Indicadores (SRS §14). Só números agregados.
*/
Route::middleware(['auth', 'verified'])->prefix('relatorios')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')->name('index');
    Route::get('pdf', [ReportController::class, 'pdf'])
        ->middleware(['permission:reports.view', 'permission:reports.export_pdf'])->name('pdf');
    Route::get('planilha', [ReportController::class, 'spreadsheet'])
        ->middleware(['permission:reports.view', 'permission:reports.export_spreadsheet'])->name('spreadsheet');
});
