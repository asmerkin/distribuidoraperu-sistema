<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scanner/{any?}', function () {
    return view('scanner');
})->where('any', '.*');
