<?php

use Illuminate\Support\Facades\Route;
use TornadoEngine\Access\Http\ExclusionsController;
use TornadoEngine\Access\Http\ProfileController;
use TornadoEngine\Access\Http\RolesController;
use TornadoEngine\Access\Http\UsersController;

// Everyone's own page: how they sign in, their roles, and what they can open.
Route::middleware(['web', 'auth'])->get('/profile', ProfileController::class)->name('profile');

Route::middleware(['web', 'auth', 'can:access.manage'])
    ->prefix('access')
    ->name('access.')
    ->group(function () {
        Route::redirect('/', '/access/users');
        Route::resource('users', UsersController::class)->except(['create', 'show']);
        Route::resource('roles', RolesController::class)->except(['show']);
        Route::resource('exclusions', ExclusionsController::class)->only(['index', 'store', 'update', 'destroy']);
    });
