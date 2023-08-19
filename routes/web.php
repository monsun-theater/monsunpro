<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::statamic('/search', 'search', [
    'title' => 'Search results',
]);

Route::get('/!/DynamicToken/refresh', 'DynamicToken@getRefresh');
Route::statamic('/sitemap.xml', 'sitemap/sitemap', ['layout' => null, 'content_type' => 'application/xml']);

Route::statamic('/password', 'password', [
    'title' => 'Passwort geschützte Seite',
]);
