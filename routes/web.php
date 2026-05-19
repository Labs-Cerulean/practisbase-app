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

// Logout Route
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Protected Onboarding Routes
|--------------------------------------------------------------------------
| Users must be logged in to access these, handled by ->middleware('auth')
*/

// Step 2: Professional Profiler
Route::get('/onboarding/profession', function () {
    // Fetch all custom professions from the database to build our autocomplete list!
    $customProfessions = \App\Models\User::whereNotIn('profession', [
        'Medical Professional', 'Architect / Perit', 'Engineer', 'Tutor / Lecturer', 'Other'
    ])->whereNotNull('profession')->distinct()->pluck('profession');

    return view('onboarding.profession', compact('customProfessions'));
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
    // Count how many clients belong to the currently logged-in user
    $clientCount = \App\Models\Client::where('user_id', auth()->id())->count();
    
    // Pass that number to the view
    return view('dashboard', compact('clientCount'));
})->middleware('auth');

// Client Management Routes
Route::get('/clients', [\App\Http\Controllers\ClientController::class, 'index']);
Route::get('/clients/create', [\App\Http\Controllers\ClientController::class, 'create']);
Route::post('/clients', [\App\Http\Controllers\ClientController::class, 'store']);
Route::get('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'show']);
Route::get('/clients/{client}/edit', [\App\Http\Controllers\ClientController::class, 'edit']);
Route::put('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'update']);


// Account Settings Routes
Route::get('/settings', [\App\Http\Controllers\ProfileController::class, 'edit']);
Route::put('/settings/profile', [\App\Http\Controllers\ProfileController::class, 'updateProfile']);
Route::put('/settings/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword']);