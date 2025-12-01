<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| QueryBuilder Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your plugin. These
| routes are loaded by the ServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::middleware(['web'])->group(function () {
    // Add your web routes here
    // Example:
    // Route::get('/query-builder', function () {
    //     return view('query-builder::index');
    // });
});
