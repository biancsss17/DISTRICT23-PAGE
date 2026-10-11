<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\Administrator;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::post('/generate', [HomeController::class, 'generate'])->name('generate');

Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
Route::get('/officers', [PublicController::class, 'officers'])->name('officers');
Route::get('/admin/login', [AuthController::class, 'create'])->name('login');
Route::post('/admin/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
Route::prefix('admin')->name('admin.')->middleware(['auth', Administrator::class])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/account', [AccountController::class, 'edit'])->name('account');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::get('/settings/{group?}', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::delete('/settings/home/media/{media}', [MediaController::class, 'destroy'])->name('settings.home.media.destroy');
    Route::put('/sections', [SettingsController::class, 'sections'])->name('sections');
    Route::resource('/officers', OfficerController::class)->except('show');
    Route::prefix('content/{type}')->where(['type' => 'news|achievements'])->name('content.')->group(function () {
        Route::get('/', [ContentController::class, 'index'])->name('index');
        Route::get('/create', [ContentController::class, 'create'])->name('create');
        Route::post('/', [ContentController::class, 'store'])->name('store');
        Route::get('/{record}/edit', [ContentController::class, 'edit'])->name('edit');
        Route::put('/{record}', [ContentController::class, 'update'])->name('update');
        Route::delete('/{record}/media/{media}', [MediaController::class, 'destroyFromContent'])->name('media.destroy');
        Route::delete('/{record}', [ContentController::class, 'destroy'])->name('destroy');
        Route::get('/{record}/preview', [ContentController::class, 'preview'])->name('preview');
    });
});
Route::get('/{type}', [PublicController::class, 'index'])->where('type', 'news|achievements')->name('content.index');
Route::get('/{type}/{slug}', [PublicController::class, 'show'])->where('type', 'news|achievements')->name('content.show');
