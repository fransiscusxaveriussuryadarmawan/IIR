<?php

use App\Http\Controllers\SearchController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('home');
});

Route::get('/test_1', function () {
    return view('index');
});

Route::post('/result', [SearchController::class, 'result']);
