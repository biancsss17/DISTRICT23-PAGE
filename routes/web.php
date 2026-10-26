<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EditorRecordsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PublicController;
use App\Http\Middleware\Administrator;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::post('/generate', [HomeController::class, 'generate'])->name('generate');

Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
Route::get('/officers', [PublicController::class, 'officers'])->name('officers');
Route::get('/admin/login', [AuthController::class, 'create'])->name('login');
Route::post('/admin/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
Route::prefix('admin')->name('admin.')->middleware(['auth', Administrator::class])->group(function () {
    Route::get('/', [\App\Http\Controllers\PageEditorController::class, 'edit'])->name('dashboard');
    Route::get('/editor', [\App\Http\Controllers\PageEditorController::class, 'edit'])->name('editor');
    Route::get('/editor/preview', [\App\Http\Controllers\PageEditorController::class, 'preview']);
    Route::put('/editor/draft', [\App\Http\Controllers\PageEditorController::class, 'save']);
    Route::delete('/editor/draft', [\App\Http\Controllers\PageEditorController::class, 'reset']);
    Route::post('/editor/publish', [\App\Http\Controllers\PageEditorController::class, 'publish']);
    Route::post('/editor/restore', [\App\Http\Controllers\PageEditorController::class, 'restore']);
    Route::post('/editor/media', [\App\Http\Controllers\PageEditorController::class, 'upload']);
    Route::get('/editor/records/{type}', [EditorRecordsController::class, 'index'])->where('type', 'news|achievements|officers');
    Route::get('/editor/records/{type}/{id}', [EditorRecordsController::class, 'show'])->where('type', 'news|achievements|officers')->whereNumber('id');
    Route::get('/editor/records/{type}/{id}/preview', [EditorRecordsController::class, 'preview'])->where('type', 'news|achievements')->whereNumber('id');
    Route::post('/editor/records/{type}', [EditorRecordsController::class, 'save'])->where('type', 'news|achievements|officers');
    Route::post('/editor/records/{type}/{id}', [EditorRecordsController::class, 'save'])->where('type', 'news|achievements|officers')->whereNumber('id');
    Route::delete('/editor/records/{type}/{id}', [EditorRecordsController::class, 'destroy'])->where('type', 'news|achievements|officers')->whereNumber('id');
    Route::delete('/editor/media/{media}', [EditorRecordsController::class, 'destroyMedia']);
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
Route::get('/{type}', [PublicController::class, 'index'])->where('type', 'news|achievements')->name('content.index');
Route::get('/{type}/{slug}', [PublicController::class, 'show'])->where('type', 'news|achievements')->name('content.show');
