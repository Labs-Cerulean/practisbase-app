<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('pricing');
});

Route::get('/login', function () {
    return view('login');
})->name('login');

// Accidental GET (refresh/bookmark) must not 405 — send them back to the form
Route::get('/login-submit', function () {
    return redirect('/login');
});

Route::post('/login-submit', [AuthController::class, 'loginSubmit']);

Route::get('/register', function () {
    return view('register');
});

Route::get('/register-submit', function () {
    return redirect('/register');
});

Route::post('/register-submit', [AuthController::class, 'registerSubmit']);

Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->middleware('guest');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->middleware(['guest', 'throttle:5,1']);
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
    ->middleware('guest')
    ->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->middleware(['guest', 'throttle:5,1']);

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated onboarding (terms checked; onboarding NOT required)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'terms'])->group(function () {
    Route::get('/onboarding/profession', function () {
        $customProfessions = \App\Models\User::whereNotIn('profession', [
            'Medical Professional', 'Architect / Perit', 'Engineer', 'Tutor / Lecturer', 'Other'
        ])->whereNotNull('profession')->distinct()->pluck('profession');

        return view('onboarding.profession', compact('customProfessions'));
    });

    Route::post('/onboarding/profession-submit', [AuthController::class, 'saveProfession']);

    Route::get('/onboarding/financial', function () {
        return view('onboarding.financial', ['user' => auth()->user()]);
    });

    Route::post('/onboarding/financial', [AuthController::class, 'saveFinancial']);

    Route::get('/onboarding/plans', function () {
        $user = auth()->user();
        $allowedTiers = \App\Support\TierPolicy::allowedTiersForProfession($user->profession);

        return view('onboarding.plans', compact('user', 'allowedTiers'));
    });

    Route::post('/onboarding/plans-submit', [AuthController::class, 'savePlan']);
});

/*
|--------------------------------------------------------------------------
| App routes (auth + terms + completed onboarding)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'terms', 'onboarded'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/clients', [ClientController::class, 'index']);
    Route::get('/clients/create', [ClientController::class, 'create']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show'])->withTrashed();
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->withTrashed();
    Route::put('/clients/{client}', [ClientController::class, 'update'])->withTrashed();
    Route::delete('/clients/{client}', [ClientController::class, 'archive'])->withTrashed();
    Route::post('/clients/{client}/restore', [ClientController::class, 'restore'])->withTrashed();

    Route::get('/settings', [ProfileController::class, 'edit']);
    Route::put('/settings/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/settings/password', [ProfileController::class, 'updatePassword']);
    Route::put('/settings/plan', [ProfileController::class, 'updatePlan']);

    Route::get('/ledger', [InvoiceController::class, 'index']);
    Route::get('/ledger/create', [InvoiceController::class, 'create']);
    Route::post('/ledger', [InvoiceController::class, 'store']);
    Route::post('/ledger/{document}/convert', [InvoiceController::class, 'convertToInvoice']);
    Route::post('/ledger/{document}/cancel', [InvoiceController::class, 'issueCreditNote']);
    Route::get('/ledger/{document}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::post('/ledger/{document}/pay', [InvoiceController::class, 'processPayment']);
    Route::get('/ledger/payments/{payment}/receipt', [InvoiceController::class, 'downloadReceipt']);
    Route::post('/ledger/{document}/credit', [InvoiceController::class, 'issueCreditNote']);
    Route::delete('/ledger/payments/{payment}', [InvoiceController::class, 'deletePayment']);
    Route::patch('/ledger/payments/{payment}/transfer', [InvoiceController::class, 'transferPayment']);
    Route::post('/ledger/{document}/refund', [InvoiceController::class, 'processRefund']);

    Route::middleware('tier:standard')->group(function () {
        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports/close-year', [ReportController::class, 'closeYear']);
        Route::post('/reports/tax-payments', [ReportController::class, 'storeTaxPayment']);
        Route::delete('/reports/tax-payments/{id}', [ReportController::class, 'destroyTaxPayment']);
    });
});
