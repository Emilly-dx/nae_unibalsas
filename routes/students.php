<?php

use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentDisabilityController;
use App\Http\Controllers\Students\StudentDocumentController;
use Illuminate\Support\Facades\Route;

/*
| M3 — Cadastro de Estudantes (SRS §9). Toda rota exige a permissão da
| ação no backend; as permissões no frontend servem apenas para a UI.
*/
Route::middleware(['auth', 'verified'])->prefix('students')->name('students.')->group(function () {
    Route::get('/', [StudentController::class, 'index'])
        ->middleware('permission:students.view')->name('index');
    Route::get('create', [StudentController::class, 'create'])
        ->middleware('permission:students.create')->name('create');
    Route::post('/', [StudentController::class, 'store'])
        ->middleware('permission:students.create')->name('store');
    Route::get('{student}', [StudentController::class, 'show'])
        ->middleware('permission:students.view')->withTrashed()->name('show');
    Route::get('{student}/edit', [StudentController::class, 'edit'])
        ->middleware('permission:students.edit')->name('edit');
    Route::put('{student}', [StudentController::class, 'update'])
        ->middleware('permission:students.edit')->name('update');
    Route::delete('{student}', [StudentController::class, 'destroy'])
        ->middleware('permission:students.delete')->name('destroy');
    Route::patch('{student}/restore', [StudentController::class, 'restore'])
        ->middleware('permission:students.delete')->withTrashed()->name('restore');

    // Dados sensíveis (CID e documentos): permissão + autorização por registro (RN-012).
    Route::middleware(['permission:students.view', 'permission:students.sensitive'])
        ->scopeBindings()
        ->group(function () {
            Route::post('{student}/disabilities', [StudentDisabilityController::class, 'store'])
                ->name('disabilities.store');
            Route::delete('{student}/disabilities/{disability}', [StudentDisabilityController::class, 'destroy'])
                ->name('disabilities.destroy');

            Route::post('{student}/documents', [StudentDocumentController::class, 'store'])
                ->name('documents.store');
            Route::get('{student}/documents/{document}/download', [StudentDocumentController::class, 'download'])
                ->whereNumber(['student', 'document'])
                ->name('documents.download');
            Route::delete('{student}/documents/{document}', [StudentDocumentController::class, 'destroy'])
                ->name('documents.destroy');
        });
});
