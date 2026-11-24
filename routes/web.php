<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Sem página pública: a raiz leva ao painel (ou ao login, para visitantes).
Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/students.php';
require __DIR__.'/attendances.php';
require __DIR__.'/referrals.php';
require __DIR__.'/demo.php';
