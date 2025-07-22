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
Route::get('/session-categories', [SessionController::class, 'sessionCategories']);


Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::get('/login/with/{provider}', 'redirectToProvider');
    Route::get('/login/with/{provider}/callback', 'handleProviderCallback');
    Route::post('login', 'login');
});
