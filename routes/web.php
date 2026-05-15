<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// 1. The Public Homepage (Landing / Pricing)
Route::get('/', function () {
    return view('pricing');
});

// 2. The Login & Register Pages
Route::get('/login', function () {
    return "Login Page Coming Soon";
});
Route::get('/register', function () {
    return view('register');
});

// The Form Submission Route
Route::post('/register-submit', [AuthController::class, 'registerSubmit']);

// Step 2 of Onboarding: Professional Profiler
Route::get('/onboarding/profession', function () {
    return "Step 2: Tell us your profession (Doctor, Architect, etc.) coming next!";
})->middleware('auth');

// 3. The Protected Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');