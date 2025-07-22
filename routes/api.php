<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('pages', PageController::class);
});



Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::get('/login/with/{provider}','redirectToProvider');
    Route::get('/login/with/{provider}/callback', 'handleProviderCallback');
    Route::post('login', 'login');
});
