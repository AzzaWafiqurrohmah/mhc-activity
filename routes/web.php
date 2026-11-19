<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Staff\DailyLogController;
use App\Http\Controllers\Sm\LogController;
use App\Http\Controllers\Sm\TodayController;
use App\Http\Controllers\Sm\RecapController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\PinController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\Cs\MemberController;
use App\Http\Controllers\Staff\Cs\RewardController;
use App\Http\Controllers\Staff\Cs\RewardStockInController;
use App\Http\Controllers\Staff\Cs\RewardStockOutController;


Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route(auth()->user()->role->homeRoute()));
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::put('/pin', [PinController::class, 'update'])->middleware('throttle:5,1')->name('pin.update');

});

Route::middleware(['auth', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', [DailyLogController::class, 'index'])->name('dashboard');
    Route::get('/riwayat', [DailyLogController::class, 'history'])->name('history');
    Route::post('/log', [DailyLogController::class, 'store'])->name('logs.store');
    Route::put('/log', [DailyLogController::class, 'update'])->name('logs.update');
});

Route::middleware(['auth', 'role:staff'])->prefix('staff/cs')->name('staff.cs.')->group(function () {

    $placeholder = fn (string $module) => fn () => view('staff.module-placeholder', [
        'title' => config("modules.{$module}.label"),
    ]);

    Route::get('do',$placeholder('cs_do'))->middleware('module:cs_do')->name('do.index');
    Route::get('wa',$placeholder('cs_wa'))->middleware('module:cs_wa')->name('wa.index');
    Route::middleware('module:cs_member')->group(function () {
        Route::get('member', [MemberController::class, 'index'])->name('member.index');
        Route::post('member', [MemberController::class, 'store'])->name('member.store');
        Route::put('member/{member}', [MemberController::class, 'update'])->name('member.update');
        Route::get('member/export', [MemberController::class, 'export'])->name('member.export');
        Route::get('member/template', [MemberController::class, 'template'])->name('member.template');
        Route::post('member/import', [MemberController::class, 'import'])->name('member.import');
    });
    Route::get('claim',  $placeholder('cs_claim'))->middleware('module:cs_claim')->name('claim.index');
    Route::middleware('module:cs_reward')->prefix('reward')->name('reward.')->group(function () {
        Route::get('/', [RewardController::class, 'index'])->name('index');
        Route::post('/', [RewardController::class, 'store'])->name('store');
        Route::put('{reward}', [RewardController::class, 'update'])->name('update');

        Route::get('stock-ins', [RewardStockInController::class, 'index'])->name('stock-ins.index');
        Route::get('stock-ins/export', [RewardStockInController::class, 'export'])->name('stock-ins.export');
        Route::post('stock-ins', [RewardStockInController::class, 'store'])->name('stock-ins.store');

        Route::get('stock-outs', [RewardStockOutController::class, 'index'])->name('stock-outs.index');
        Route::get('stock-outs/export', [RewardStockOutController::class, 'export'])->name('stock-outs.export');
        Route::post('stock-outs', [RewardStockOutController::class, 'store'])->name('stock-outs.store');
        Route::get('members/lookup', [RewardStockOutController::class, 'memberLookup'])->name('members.lookup');
        Route::get('export', [RewardController::class, 'export'])->name('export');
    });
});


Route::middleware(['auth', 'role:sm'])->prefix('sm')->name('sm.')->group(function () {
    // sementara placeholder, nanti diganti controller sendiri
    Route::get('/', [TodayController::class, 'index'])->name('dashboard');
    Route::get('recap', [RecapController::class, 'index'])->name('recap.index');
    Route::get('recap/{user}', [RecapController::class, 'show'])->name('recap.show');
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');
});

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


