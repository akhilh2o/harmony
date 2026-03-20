<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SessionController;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('pages', PageController::class);
Route::get   ('/pages/{page}', [PageController::class, 'show']);

// ─── SESSION AUDIOS (public) ──────────────────────────────────
Route::get('/session-categories',           [SessionController::class, 'sessionCategories']);
Route::get('/session-audios',               [SessionController::class, 'sessionAudios']);
Route::get('/session-audios/free',          [SessionController::class, 'freeSessionAudios']);     // ⚠️ {id} se pehle zaroori
Route::get('/session-audios/category/{id}', [SessionController::class, 'sessionAudioByCategoryId']);
// Route::get('/session-audios/{id}',          [SessionController::class, 'sessionAudioById']);      // last mein
Route::get('/session-audios/{param}',       [SessionController::class, 'sessionAudioByIdOrSlug']); // ← ek hi route

// ─── AUTH ROUTES ──────────────────────────────────────────────
Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login',    'login');
    Route::get('/login/with/{provider}',          'redirectToProvider');
    Route::get('/login/with/{provider}/callback', 'handleProviderCallback');
});

// ─── PROTECTED ROUTES (Bearer Token required) ─────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Logout
    Route::post('logout', [AuthController::class, 'logout']);

    // User Profile
    Route::get ('user/profile',         [UserController::class, 'profile']);
    Route::put ('user/profile',         [UserController::class, 'updateProfile']);
    Route::post('user/change-password', [UserController::class, 'changePassword']);

    // Premium sessions (auth required)
    Route::get('/session-audios/premium', [SessionController::class, 'premiumSessionAudios']);

    // Playlists
    Route::post  ('/playlists',                       [SessionController::class, 'createPlaylist']);
    Route::get   ('/playlists',                       [SessionController::class, 'getPlaylists']);
    Route::get   ('/playlists/{id}',                  [SessionController::class, 'getPlaylistById']);
    Route::put   ('/playlists/{id}',                  [SessionController::class, 'updatePlaylist']);
    Route::delete('/playlists/{id}',                  [SessionController::class, 'deletePlaylist']);
    Route::post  ('/playlists/{id}/audios',           [SessionController::class, 'addAudioToPlaylist']);
    Route::delete('/playlists/{id}/audios/{audioId}', [SessionController::class, 'removeAudioFromPlaylist']);
    Route::post  ('/playlists/{id}/reorder',          [SessionController::class, 'reorderPlaylistAudios']);
});