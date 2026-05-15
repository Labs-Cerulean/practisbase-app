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

// Step 2: Professional Profiler (Protected)
Route::get('/onboarding/profession', function () {
    return view('onboarding.profession');
})->middleware('auth');

Route::post('/onboarding/profession-submit', [\App\Http\Controllers\AuthController::class, 'saveProfession'])
    ->middleware('auth');

// Step 3: Plan Selection (Placeholder for now)
Route::get('/onboarding/plans', function () {
    return "Step 3: Choose Free, Standard, or Pro (Stripe Integration coming next!)";
})->middleware('auth');

// 3. The Protected Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');


