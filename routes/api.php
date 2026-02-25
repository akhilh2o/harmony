<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::resource('pages', PageController::class);
Route::get('/session-audios', [SessionController::class, 'sessionAudios']);
Route::get('/session-audios/{id}', [SessionController::class, 'sessionAudioById']);
Route::get('/session-audios/category/{id}', [SessionController::class, 'sessionAudioByCategoryId']);
Route::get('/session-categories', [SessionController::class, 'sessionCategories']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/playlists', [SessionController::class, 'createPlaylist']);
    Route::get('/playlists', [SessionController::class, 'getPlaylists']);
    Route::get('/playlists/{id}', [SessionController::class, 'getPlaylistById']);
    Route::put('/playlists/{id}', [SessionController::class, 'updatePlaylist']);
    Route::delete('/playlists/{id}', [SessionController::class, 'deletePlaylist']);
    Route::post('/playlists/{id}/audios', [SessionController::class, 'addAudioToPlaylist']);
    Route::delete('/playlists/{id}/audios/{audioId}', [SessionController::class, 'removeAudioFromPlaylist']);
});


Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::get('/login/with/{provider}', 'redirectToProvider');
    Route::get('/login/with/{provider}/callback', 'handleProviderCallback');
    Route::post('login', 'login');
});
