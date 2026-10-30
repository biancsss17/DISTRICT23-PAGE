<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EditorMediaController;
use App\Http\Controllers\EditorRecordsController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageEditorController;
use App\Http\Controllers\PublicController;
use App\Http\Middleware\Administrator;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');

Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
Route::get('/officers', [PublicController::class, 'officers'])->name('officers');
Route::get('/songs', [PublicController::class, 'songs'])->name('songs');
Route::get('/map', [PublicController::class, 'map'])->name('map');
Route::get('/admin/login', [AuthController::class, 'create'])->name('login');
Route::post('/admin/login', [AuthController::class, 'store'])->middleware('throttle:10,1');
Route::prefix('admin')->name('admin.')->middleware(['auth', Administrator::class])->group(function () {
    Route::get('/', [PageEditorController::class, 'edit'])->name('dashboard');
    Route::get('/editor', [PageEditorController::class, 'edit'])->name('editor');
    Route::get('/editor/preview', [PageEditorController::class, 'preview']);
    Route::put('/editor/draft', [PageEditorController::class, 'save']);
    Route::delete('/editor/draft', [PageEditorController::class, 'reset']);
    Route::post('/editor/publish', [PageEditorController::class, 'publish']);
    Route::post('/editor/restore', [PageEditorController::class, 'restore']);
    Route::post('/editor/media', [EditorMediaController::class, 'store']);
    Route::get('/editor/records/{type}', [EditorRecordsController::class, 'index'])->where('type', 'news|achievements|officers|songs|centers');
    Route::get('/editor/records/{type}/{id}', [EditorRecordsController::class, 'show'])->where('type', 'news|achievements|officers|songs|centers')->whereNumber('id');
    Route::get('/editor/records/{type}/{id}/preview', [EditorRecordsController::class, 'preview'])->where('type', 'news|achievements')->whereNumber('id');
    Route::post('/editor/records/{type}', [EditorRecordsController::class, 'save'])->where('type', 'news|achievements|officers|songs|centers');
    Route::post('/editor/records/{type}/{id}', [EditorRecordsController::class, 'save'])->where('type', 'news|achievements|officers|songs|centers')->whereNumber('id');
    Route::delete('/editor/records/{type}/{id}', [EditorRecordsController::class, 'destroy'])->where('type', 'news|achievements|officers|songs|centers')->whereNumber('id');
    Route::delete('/editor/media/{media}', [EditorMediaController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
Route::get('/{type}', [PublicController::class, 'index'])->where('type', 'news|achievements')->name('content.index');
Route::get('/{type}/{slug}', [PublicController::class, 'show'])->where('type', 'news|achievements')->name('content.show');
