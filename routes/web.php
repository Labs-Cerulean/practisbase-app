<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
});

// New Public Pricing Route
Route::get('/pricing', function () {
    return view('pricing');
});