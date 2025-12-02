<?php

use App\Http\Controllers\SearchController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::post('/result', [SearchController::class, 'result']);
