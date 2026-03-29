<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SubscriptionPlanController;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UserActivityController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::resource('pages', PageController::class);
Route::get('/pages/{page}', [PageController::class, 'show']);

Route::get('/session-categories',           [SessionController::class, 'sessionCategories']);
Route::get('/session-audios',               [SessionController::class, 'sessionAudios']);
Route::get('/session-audios/free',          [SessionController::class, 'freeSessionAudios']);
Route::get('/session-audios/category/{id}', [SessionController::class, 'sessionAudioByCategoryId']);
Route::get('/session-audios/{param}',       [SessionController::class, 'sessionAudioByIdOrSlug']);

// ─── SUBSCRIPTION PLANS (public) ─────────────────────────────
Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);

// Store webhooks — no auth, Google/Apple call these
Route::post('/webhooks/google-play', [SubscriptionPlanController::class, 'googlePlayWebhook']);
Route::post('/webhooks/apple',       [SubscriptionPlanController::class, 'appleWebhook']);

// ─── AUTH ROUTES ──────────────────────────────────────────────
Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login',    'login');
        Route::post('/social-login', [AuthController::class, 'socialLogin']);

    Route::get('/login/with/{provider}',          'redirectToProvider');
    Route::get('/login/with/{provider}/callback', 'handleProviderCallback');
});
Route::post('/support', [SupportController::class, 'submit']);

// ─── PROTECTED ROUTES (Bearer Token required) ─────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('logout', [AuthController::class, 'logout']);
    // User Profile
    Route::get ('user/profile',         [UserController::class, 'profile']);
    Route::put ('user/profile',         [UserController::class, 'updateProfile']);
    Route::post('user/change-password', [UserController::class, 'changePassword']);

    Route::get('/user/stats', [UserActivityController::class, 'getStats']);

    // Subscription
    Route::get   ('user/subscription',          [SubscriptionPlanController::class, 'status']);
    Route::post  ('user/subscription/activate', [SubscriptionPlanController::class, 'activate']);
    Route::post  ('user/subscription/verify',   [SubscriptionPlanController::class, 'verify']);
    Route::delete('user/subscription/cancel',   [SubscriptionPlanController::class, 'cancel']);

    Route::get('/session-audios/premium', [SessionController::class, 'premiumSessionAudios']);
    Route::post  ('/activity', [UserActivityController::class, 'trackPlay']);
    Route::get   ('/activity', [UserActivityController::class, 'getActivities']);
    Route::delete('/activity', [UserActivityController::class, 'clearActivities']);
    // ─── Wishlist ─────────────────────────────────────
    Route::get   ('/wishlist',                  [UserActivityController::class, 'getWishlist']);
    Route::post  ('/wishlist',                  [UserActivityController::class, 'addToWishlist']);
    Route::post  ('/wishlist/toggle',           [UserActivityController::class, 'toggleWishlist']);
    Route::delete('/wishlist/{sessionAudioId}', [UserActivityController::class, 'removeFromWishlist']);

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