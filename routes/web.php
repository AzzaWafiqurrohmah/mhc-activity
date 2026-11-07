<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route(auth()->user()->role->homeRoute()));
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'role:staff'])->get('/staff', fn () =>
    view('dashboard', ['title' => 'Dashboard Staff'])
)->name('staff.dashboard');

Route::middleware(['auth', 'role:sm'])->get('/sm', fn () =>
    view('dashboard', ['title' => 'Dashboard Store Manager'])
)->name('sm.dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.divisions.index'))->name('dashboard');

    Route::resource('divisions', DivisionController::class)->only(['index', 'store', 'edit', 'update']);
    Route::patch('divisions/{division}/toggle', [DivisionController::class, 'toggle'])
        ->name('divisions.toggle');

    Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update']);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');

    Route::resource('users', UserController::class)->only(['index', 'store', 'update']);
    Route::patch('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::patch('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
});

// Route::resource('divisions', DivisionController::class)->only(['index', 'store', 'update']);


