<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     Artisan::call('optimize:clear');
//     return view('welcome');
// });

Route::get('/', function () {
    return redirect('/admin/login');
});
