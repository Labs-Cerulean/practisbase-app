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

// Step 3: Financial & Compliance Setup
Route::get('/onboarding/financial', function () {
    return view('onboarding.financial', ['user' => auth()->user()]);
})->middleware('auth');

Route::post('/onboarding/financial', [AuthController::class, 'saveFinancial'])
    ->middleware('auth');

// Step 4: Plan Selection
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
    $userId = auth()->id();
    
    // 1. Count Clients
    $clientCount = \App\Models\Client::where('user_id', $userId)->count();
    
    // 2. Calculate Pending Invoices (Unpaid or Overdue)
    $pendingInvoicesTotal = \App\Models\Invoice::where('user_id', $userId)
        ->whereIn('status', ['unpaid', 'overdue'])
        ->sum('total');
    
    return view('dashboard', compact('clientCount', 'pendingInvoicesTotal'));
})->middleware('auth');

// Client Management Routes
Route::get('/clients', [\App\Http\Controllers\ClientController::class, 'index'])->middleware('auth');
Route::get('/clients/create', [\App\Http\Controllers\ClientController::class, 'create'])->middleware('auth');
Route::post('/clients', [\App\Http\Controllers\ClientController::class, 'store'])->middleware('auth');
Route::get('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'show'])->middleware('auth');
Route::get('/clients/{client}/edit', [\App\Http\Controllers\ClientController::class, 'edit'])->middleware('auth');
Route::put('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'update'])->middleware('auth');

// Account Settings Routes
Route::get('/settings', [\App\Http\Controllers\ProfileController::class, 'edit'])->middleware('auth');
Route::put('/settings/profile', [\App\Http\Controllers\ProfileController::class, 'updateProfile'])->middleware('auth');
Route::put('/settings/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->middleware('auth');

// Financial Ledger Routes
Route::get('/ledger', [\App\Http\Controllers\InvoiceController::class, 'index'])->middleware('auth');
Route::get('/ledger/create', [\App\Http\Controllers\InvoiceController::class, 'create'])->middleware('auth');
Route::post('/ledger', [\App\Http\Controllers\InvoiceController::class, 'store'])->middleware('auth');
Route::post('/ledger/{document}/convert', [\App\Http\Controllers\InvoiceController::class, 'convertToInvoice'])->middleware('auth');
Route::post('/ledger/{document}/cancel', [\App\Http\Controllers\InvoiceController::class, 'issueCreditNote'])->middleware('auth');
Route::get('/ledger/{document}/pdf', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf'])->middleware('auth');