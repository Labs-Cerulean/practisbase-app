<?php
use Illuminate\Support\Facades\Route;

// 1. The Public Homepage (Landing / Pricing)
Route::get('/', function () {
    return view('pricing');
});

// 2. The Login & Register Pages (Placeholders for now)
Route::get('/login', function () {
    return "Login Page Coming Soon"; // We will build this next
});
Route::get('/register', function () {
    return view('register');
});

// 3. The Protected Dashboard
// Note: We will add the ->middleware('auth') later to lock this down!
Route::get('/dashboard', function () {
    return view('dashboard');
});