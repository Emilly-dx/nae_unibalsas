<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| M9 — Segurança + Auditoria + LGPD (SRS §15): contas da equipe,
| perfis de acesso e trilha de auditoria.
*/
Route::middleware(['auth', 'verified'])->name('admin.')->group(function () {
    Route::prefix('usuarios')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view')->name('index');
        Route::get('novo', [UserController::class, 'create'])->middleware('permission:users.create')->name('create');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create')->name('store');

        Route::whereNumber('user')->group(function () {
            Route::get('{user}/editar', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('edit');
            Route::put('{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('update');
            Route::patch('{user}/desativar', [UserController::class, 'deactivate'])->middleware('permission:users.delete')->name('deactivate');
            Route::patch('{user}/reativar', [UserController::class, 'reactivate'])->middleware('permission:users.delete')->name('reactivate');
            Route::post('{user}/link-senha', [UserController::class, 'sendPasswordResetLink'])
                ->middleware(['permission:users.edit', 'throttle:6,1'])->name('password-link');
        });
    });

    Route::get('perfis-de-acesso', [RoleController::class, 'index'])
        ->middleware('permission:acl.manage_permissions')->name('roles.index');
    Route::put('perfis-de-acesso/{role}', [RoleController::class, 'update'])
        ->whereNumber('role')->middleware('permission:acl.manage_permissions')->name('roles.update');

    Route::get('auditoria', [AuditController::class, 'index'])
        ->middleware('permission:audit.view')->name('audit.index');
});
