<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| These routes are accessible to anyone on the internet.
*/

// The Public Landing Page & Pricing
Route::get('/', function () {
    return view('pricing');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Routes for logging in and creating a new account.
*/

// The actual Login view
Route::get('/login', function () {
    return view('login');
})->name('login');

// The route that processes the login form
Route::post('/login-submit', [AuthController::class, 'loginSubmit']);

// The Registration Page & Form Submission
Route::get('/register', function () {
    return view('register');
});
Route::post('/register-submit', [AuthController::class, 'registerSubmit']);

/*
|--------------------------------------------------------------------------
| Protected Onboarding Routes
|--------------------------------------------------------------------------
| Users must be logged in to access these, handled by ->middleware('auth')
*/

// Step 2: Professional Profiler
Route::get('/onboarding/profession', function () {
    return view('onboarding.profession');
})->middleware('auth');

Route::post('/onboarding/profession-submit', [AuthController::class, 'saveProfession'])
    ->middleware('auth');

// Step 3: Plan Selection (Dev Bypass)
Route::get('/onboarding/plans', function () {
    return view('onboarding.plans');
})->middleware('auth');

Route::post('/onboarding/plans-submit', [AuthController::class, 'savePlan'])
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| Protected Application Routes (The Dashboard)
|--------------------------------------------------------------------------
| The core app functionality. Only accessible to logged-in, onboarded users.
*/

// The Master Dashboard
Route::get('/dashboard', function () {
    return "Welcome to the Dashboard! Your onboarding is complete.";
})->middleware('auth');