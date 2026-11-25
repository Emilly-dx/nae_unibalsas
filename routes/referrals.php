<?php

use App\Http\Controllers\Referrals\ReferralController;
use App\Http\Controllers\Students\FollowUpController;
use Illuminate\Support\Facades\Route;

/*
| M5 — Acompanhamento (encerramento) e M6 — Encaminhamentos (SRS §11, §12).
| Regras por registro ficam na ReferralPolicy.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('encaminhamentos')->name('referrals.')->group(function () {
        Route::get('/', [ReferralController::class, 'index'])
            ->middleware('permission:referrals.view')->name('index');
        Route::get('novo', [ReferralController::class, 'create'])
            ->middleware('permission:referrals.create')->name('create');
        Route::post('/', [ReferralController::class, 'store'])
            ->middleware('permission:referrals.create')->name('store');

        Route::whereNumber('referral')->group(function () {
            Route::get('{referral}', [ReferralController::class, 'show'])
                ->middleware('permission:referrals.view')->name('show');
            Route::patch('{referral}/situacao', [ReferralController::class, 'updateStatus'])->name('status');
            Route::patch('{referral}/cancelar', [ReferralController::class, 'cancel'])->name('cancel');
            Route::patch('{referral}/reabrir', [ReferralController::class, 'reopen'])->name('reopen');
        });
    });

    Route::post('students/{student}/acompanhamento/encerrar', [FollowUpController::class, 'close'])
        ->middleware('permission:attendances.finish')->name('students.follow-up.close');
});
