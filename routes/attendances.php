<?php

use App\Http\Controllers\Attendances\AttendanceController;
use App\Http\Controllers\Triage\SupportRequestController;
use App\Http\Controllers\Triage\TriageController;
use Illuminate\Support\Facades\Route;

/*
| M4 — Triagem + Atendimentos (SRS §10). As permissões de cada ação são
| validadas no backend; regras por registro ficam na AttendancePolicy.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('triagem', [TriageController::class, 'index'])->name('triage.index');

    Route::prefix('triagem/solicitacoes')->name('triage.requests.')->group(function () {
        Route::get('nova', [SupportRequestController::class, 'create'])
            ->middleware('permission:triage.create')->name('create');
        Route::post('/', [SupportRequestController::class, 'store'])
            ->middleware('permission:triage.create')->name('store');
        Route::get('{supportRequest}', [SupportRequestController::class, 'show'])
            ->middleware('permission:triage.view')->name('show');
        Route::post('{supportRequest}/agendar', [SupportRequestController::class, 'schedule'])
            ->middleware('permission:triage.perform')->name('schedule');
        Route::post('{supportRequest}/devolver', [SupportRequestController::class, 'giveBack'])
            ->middleware('permission:triage.perform')->name('give-back');
    });

    Route::prefix('atendimentos')->name('attendances.')->group(function () {
        Route::get('agendar', [AttendanceController::class, 'create'])
            ->middleware('permission:attendances.schedule')->name('create');
        Route::post('/', [AttendanceController::class, 'store'])
            ->middleware('permission:attendances.schedule')->name('store');

        Route::get('registrar', [AttendanceController::class, 'registerForm'])
            ->middleware('permission:attendances.create')->name('register.create');
        Route::post('registrar', [AttendanceController::class, 'storeRegistration'])
            ->middleware('permission:attendances.create')->name('register.store');

        Route::whereNumber('attendance')->group(function () {
            Route::get('{attendance}', [AttendanceController::class, 'show'])
                ->middleware('permission:attendances.view')->name('show');
            Route::get('{attendance}/registrar', [AttendanceController::class, 'registerForm'])
                ->middleware('permission:attendances.create')->name('register.edit');
            Route::put('{attendance}/registro', [AttendanceController::class, 'updateRegistration'])
                ->middleware('permission:attendances.create')->name('register.update');
            Route::patch('{attendance}/cancelar', [AttendanceController::class, 'cancel'])->name('cancel');
            Route::patch('{attendance}/concluir', [AttendanceController::class, 'finish'])->name('finish');
        });
    });
});
