<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\AccountantDownloadController;
use App\Http\Controllers\DataBackupController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Pro\Medical\VaultController as MedicalVaultController;
use App\Http\Controllers\Pro\Medical\PatientController;
use App\Http\Controllers\Pro\Medical\ClinicalEntryController;
use App\Http\Controllers\Pro\Medical\ClinicalAttachmentController;
use App\Http\Controllers\Pro\Medical\ClinicalEntryPdfController;
use App\Http\Controllers\Pro\Medical\ClinicalImportController;
use App\Http\Controllers\Pro\Medical\ClinicalNoteTemplateController;
use App\Http\Controllers\Pro\Medical\MedicalBackupController;
use App\Http\Controllers\Pro\Medical\PrescriptionCatalogController;
use App\Http\Controllers\Pro\Medical\StampableLedgerController;
use App\Http\Controllers\Pro\Medical\VaultDeviceController;
use App\Http\Controllers\Pro\Architect\ProjectController as ArchitectProjectController;
use App\Http\Controllers\Pro\Architect\PaApplicationController as ArchitectPaController;
use App\Http\Controllers\Pro\Architect\DocumentController as ArchitectDocumentController;
use App\Http\Controllers\Pro\Architect\TemplateController as ArchitectTemplateController;
use App\Http\Controllers\Pro\Architect\LicenceController as ArchitectLicenceController;
use App\Http\Controllers\Pro\Architect\ConditionReportController as ArchitectConditionReportController;
use App\Http\Controllers\Pro\Architect\MethodStatementController as ArchitectMethodStatementController;
use App\Http\Controllers\Pro\CertificateController;
use App\Http\Controllers\Pro\Engineer\ProjectController as EngineerProjectController;
use App\Http\Controllers\Pro\Engineer\PaApplicationController as EngineerPaController;
use App\Http\Controllers\Pro\Engineer\DocumentController as EngineerDocumentController;
use App\Http\Controllers\Pro\Engineer\CertificateController as EngineerCertificateController;
use App\Http\Controllers\Pro\Engineer\ReportController as EngineerReportController;
use App\Http\Controllers\Pro\Engineer\EquipmentController as EngineerEquipmentController;
use App\Http\Controllers\Company\DeskController as CompanyDeskController;
use App\Http\Controllers\Company\PlatformDashboardController as CompanyPlatformDashboardController;
use App\Http\Controllers\Company\ProfileController as CompanyProfileController;
use App\Http\Controllers\Company\ClientController as CompanyClientController;
use App\Http\Controllers\Company\InvoiceController as CompanyInvoiceController;
use App\Http\Controllers\Company\ExpenseController as CompanyExpenseController;
use App\Http\Controllers\Company\AccountsController as CompanyAccountsController;
use App\Http\Controllers\Company\BankController as CompanyBankController;
use App\Http\Controllers\Company\DividendController as CompanyDividendController;
use App\Http\Controllers\Company\RecurringInvoiceController as CompanyRecurringInvoiceController;
use App\Http\Controllers\Company\BetaInviteController as CompanyBetaInviteController;
use App\Http\Controllers\Company\ContentStudioController as CompanyContentStudioController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\CommunityFeedbackController;
use App\Http\Controllers\CommunityFeedbackOperatorController;
use App\Http\Controllers\DocumentStampController;
use App\Http\Controllers\DocumentStamperController;
use App\Http\Controllers\LegalController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/pricing', function () {
    return view('pricing');
});

Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/msa', [LegalController::class, 'msa'])->name('msa');

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

// Accidental GET (bookmark, refresh, mobile navigate) must not 405 — stay on POST for real logout
Route::get('/logout', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated onboarding (terms checked; onboarding NOT required)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'terms'])->group(function () {
    Route::get('/onboarding/profession', function () {
        if (auth()->user()->beta_invite_code_id) {
            return redirect('/onboarding/financial');
        }

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
        if ($user->beta_invite_code_id) {
            $home = $user->canAccessCompanyBooks() ? '/company' : '/dashboard';

            return redirect($home);
        }
        $allowedTiers = \App\Support\TierPolicy::allowedTiersForProfession($user->profession);

        return view('onboarding.plans', compact('user', 'allowedTiers'));
    });

    Route::post('/onboarding/plans-submit', [AuthController::class, 'savePlan']);
});

/*
|--------------------------------------------------------------------------
| WebAuthn finish (ticket-auth; session may be gone after mobile biometrics)
|--------------------------------------------------------------------------
*/

Route::post('/pro/medical/vault/devices/register', [VaultDeviceController::class, 'register'])
    ->middleware('throttle:20,1');
Route::post('/pro/medical/vault/devices/unlock', [VaultDeviceController::class, 'unlock'])
    ->middleware('throttle:20,1');

/*
|--------------------------------------------------------------------------
| App routes (auth + terms + completed onboarding)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'terms', 'onboarded', 'company_shell'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/accounts', [DashboardController::class, 'accounts']);

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

    Route::get('/community/feedback', [CommunityFeedbackController::class, 'index']);
    Route::get('/community/feedback/create', [CommunityFeedbackController::class, 'create']);
    Route::post('/community/feedback', [CommunityFeedbackController::class, 'store']);
    Route::middleware('company_books')->group(function () {
        Route::get('/community/feedback/inbox', [CommunityFeedbackOperatorController::class, 'index']);
        Route::get('/community/feedback/inbox/{id}', [CommunityFeedbackOperatorController::class, 'show'])->whereNumber('id');
        Route::post('/community/feedback/inbox/{id}/reply', [CommunityFeedbackOperatorController::class, 'reply'])->whereNumber('id');
        Route::put('/community/feedback/inbox/{id}/status', [CommunityFeedbackOperatorController::class, 'updateStatus'])->whereNumber('id');
        Route::delete('/community/feedback/inbox/{id}', [CommunityFeedbackOperatorController::class, 'destroy'])->whereNumber('id');
    });
    Route::get('/community/feedback/{id}', [CommunityFeedbackController::class, 'show'])->whereNumber('id');
    Route::post('/community/feedback/{id}/reply', [CommunityFeedbackController::class, 'reply'])->whereNumber('id');

    Route::get('/exports/backup', [DataBackupController::class, 'form']);
    Route::post('/exports/backup', [DataBackupController::class, 'download'])->middleware('throttle:5,1');

    Route::middleware('stamper')->prefix('stamper')->group(function () {
        Route::get('/', [DocumentStamperController::class, 'index']);
        Route::get('/stamps', [DocumentStampController::class, 'index']);
        Route::get('/stamps/create', [DocumentStampController::class, 'create']);
        Route::post('/stamps', [DocumentStampController::class, 'store']);
        Route::get('/stamps/{id}/edit', [DocumentStampController::class, 'edit'])->whereNumber('id');
        Route::put('/stamps/{id}', [DocumentStampController::class, 'update'])->whereNumber('id');
        Route::delete('/stamps/{id}', [DocumentStampController::class, 'destroy'])->whereNumber('id');
        Route::post('/stamps/{id}/default', [DocumentStampController::class, 'makeDefault'])->whereNumber('id');
        Route::post('/stamps/{id}/composed', [DocumentStampController::class, 'saveComposed'])->whereNumber('id');
    });

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
        Route::get('/reports/ta22.pdf', [ReportController::class, 'downloadTa22']);
        Route::get('/reports/vat.pdf', [ReportController::class, 'downloadVatPeriod']);

        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::get('/expenses/create', [ExpenseController::class, 'create']);
        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::put('/expenses/business-use', [ExpenseController::class, 'updateBusinessUse']);
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy']);
        Route::get('/expenses/{expense}/receipt', [ExpenseController::class, 'downloadReceipt']);
        Route::post('/expenses/{expense}/receipt', [ExpenseController::class, 'attachReceipt']);

        Route::get('/exports/accountant', [AccountantDownloadController::class, 'index']);
        Route::post('/exports/accountant', [AccountantDownloadController::class, 'download']);
    });

    Route::put('/settings/branding', [ProfileController::class, 'updateBranding']);

    Route::middleware('pro:med')->prefix('pro/medical')->group(function () {
        Route::get('/vault/setup', [MedicalVaultController::class, 'setupForm']);
        Route::post('/vault/setup', [MedicalVaultController::class, 'setup']);
        Route::get('/vault/reveal', [MedicalVaultController::class, 'revealForm']);
        Route::post('/vault/reveal/confirm', [MedicalVaultController::class, 'confirmCodeSaved'])->middleware('throttle:10,1');
        Route::get('/vault/unlock', [MedicalVaultController::class, 'unlockForm']);
        Route::post('/vault/unlock', [MedicalVaultController::class, 'unlock'])->middleware('throttle:10,1');
        Route::post('/vault/lock', [MedicalVaultController::class, 'lock']);
        Route::get('/vault/backup', [MedicalBackupController::class, 'form'])->middleware('vault');
        Route::post('/vault/backup', [MedicalBackupController::class, 'download'])->middleware(['vault', 'throttle:5,1']);
        Route::get('/vault/client-dek', [MedicalVaultController::class, 'clientDek'])->middleware(['vault', 'throttle:30,1']);

        Route::get('/vault/devices', [VaultDeviceController::class, 'index']);
        Route::post('/vault/devices/unlock-options', [VaultDeviceController::class, 'unlockOptions'])->middleware('throttle:20,1');
        Route::post('/vault/devices/register-options', [VaultDeviceController::class, 'registerOptions'])->middleware('vault');
        Route::delete('/vault/devices/{device}', [VaultDeviceController::class, 'destroy']);

        Route::middleware('vault')->group(function () {
            Route::get('/stampables', [StampableLedgerController::class, 'index']);
            Route::post('/issue-codes/lookup', [StampableLedgerController::class, 'lookupIssueCode']);
            Route::get('/prescription-catalog', [PrescriptionCatalogController::class, 'suggest']);
            Route::get('/patients', [PatientController::class, 'index']);
            Route::get('/patients/create', [PatientController::class, 'create']);
            Route::post('/patients', [PatientController::class, 'store']);
            Route::get('/import', [ClinicalImportController::class, 'form']);
            Route::post('/import/commit', [ClinicalImportController::class, 'commit'])->middleware('throttle:20,1');
            Route::get('/templates', [ClinicalNoteTemplateController::class, 'index']);
            Route::get('/templates/create', [ClinicalNoteTemplateController::class, 'create']);
            Route::post('/templates', [ClinicalNoteTemplateController::class, 'store']);
            Route::get('/templates/{template}/edit', [ClinicalNoteTemplateController::class, 'edit']);
            Route::put('/templates/{template}', [ClinicalNoteTemplateController::class, 'update']);
            Route::delete('/templates/{template}', [ClinicalNoteTemplateController::class, 'destroy']);
            Route::get('/patients/{patient}', [PatientController::class, 'show']);
            Route::get('/patients/{patient}/edit', [PatientController::class, 'edit']);
            Route::put('/patients/{patient}', [PatientController::class, 'update']);
            Route::put('/patients/{patient}/billing-link', [PatientController::class, 'updateBillingLink']);
            Route::post('/patients/{patient}/billing-client', [PatientController::class, 'createBillingClient']);
            Route::get('/patients/{patient}/entries/create', [ClinicalEntryController::class, 'create']);
            Route::post('/patients/{patient}/entries', [ClinicalEntryController::class, 'store']);
            Route::get('/patients/{patient}/entries/{entry}/edit', [ClinicalEntryController::class, 'edit']);
            Route::put('/patients/{patient}/entries/{entry}', [ClinicalEntryController::class, 'update']);
            Route::get('/patients/{patient}/entries/{entry}/issue', [ClinicalEntryController::class, 'issueGetFallback']);
            Route::post('/patients/{patient}/entries/{entry}/issue', [ClinicalEntryController::class, 'issue']);
            Route::get('/patients/{patient}/entries/{entry}/pdf', [ClinicalEntryPdfController::class, 'download']);
            Route::post('/patients/{patient}/entries/{entry}/attachments', [ClinicalAttachmentController::class, 'store']);
            Route::get('/patients/{patient}/attachments/{attachment}/download', [ClinicalAttachmentController::class, 'download']);
        });
    });

    Route::middleware('pro:arch')->prefix('pro/architect')->group(function () {
        Route::get('/projects', [ArchitectProjectController::class, 'index']);
        Route::get('/projects/create', [ArchitectProjectController::class, 'create']);
        Route::post('/projects', [ArchitectProjectController::class, 'store']);
        Route::get('/projects/{project}', [ArchitectProjectController::class, 'show']);
        Route::get('/projects/{project}/edit', [ArchitectProjectController::class, 'edit']);
        Route::put('/projects/{project}', [ArchitectProjectController::class, 'update']);
        Route::post('/projects/{project}/parties', [ArchitectProjectController::class, 'storeParty']);
        Route::delete('/projects/{project}/parties/{party}', [ArchitectProjectController::class, 'destroyParty']);
        Route::get('/geocode/reverse', [ArchitectProjectController::class, 'reverseGeocode']);

        Route::get('/projects/{project}/pa/create', [ArchitectPaController::class, 'create']);
        Route::post('/projects/{project}/pa', [ArchitectPaController::class, 'store']);
        Route::get('/pa/{pa}', [ArchitectPaController::class, 'show']);
        Route::get('/pa/{pa}/edit', [ArchitectPaController::class, 'edit']);
        Route::put('/pa/{pa}', [ArchitectPaController::class, 'update']);

        Route::get('/documents', [ArchitectDocumentController::class, 'index']);
        Route::get('/documents/create', [ArchitectDocumentController::class, 'create']);
        Route::post('/documents', [ArchitectDocumentController::class, 'store']);
        Route::get('/documents/{document}', [ArchitectDocumentController::class, 'show']);
        Route::put('/documents/{document}', [ArchitectDocumentController::class, 'update']);
        Route::post('/documents/{document}/revisions', [ArchitectDocumentController::class, 'uploadRevision']);
        Route::get('/documents/{document}/revisions/{revision}/download', [ArchitectDocumentController::class, 'download']);

        Route::get('/condition-reports', [ArchitectConditionReportController::class, 'index']);
        Route::get('/condition-reports/create', [ArchitectConditionReportController::class, 'create']);
        Route::post('/condition-reports', [ArchitectConditionReportController::class, 'store']);
        Route::get('/condition-reports/{report}', [ArchitectConditionReportController::class, 'show']);
        Route::get('/condition-reports/{report}/edit', [ArchitectConditionReportController::class, 'edit']);
        Route::put('/condition-reports/{report}', [ArchitectConditionReportController::class, 'update']);
        Route::post('/condition-reports/{report}/stamp', [ArchitectConditionReportController::class, 'stamp']);
        Route::get('/condition-reports/{report}/pdf', [ArchitectConditionReportController::class, 'downloadPdf']);
        Route::get('/condition-reports/{report}/photos/{photo}', [ArchitectConditionReportController::class, 'downloadPhoto']);

        Route::get('/method-statements', [ArchitectMethodStatementController::class, 'index']);
        Route::get('/method-statements/create', [ArchitectMethodStatementController::class, 'create']);
        Route::post('/method-statements', [ArchitectMethodStatementController::class, 'store']);
        Route::get('/method-statements/{statement}', [ArchitectMethodStatementController::class, 'show']);
        Route::get('/method-statements/{statement}/edit', [ArchitectMethodStatementController::class, 'edit']);
        Route::put('/method-statements/{statement}', [ArchitectMethodStatementController::class, 'update']);
        Route::post('/method-statements/{statement}/stamp', [ArchitectMethodStatementController::class, 'stamp']);
        Route::get('/method-statements/{statement}/pdf', [ArchitectMethodStatementController::class, 'downloadPdf']);
        Route::get('/method-statements/{statement}/photos/{photo}', [ArchitectMethodStatementController::class, 'downloadPhoto']);

        Route::get('/templates', [ArchitectTemplateController::class, 'index']);
        Route::get('/templates/{key}/fill', [ArchitectTemplateController::class, 'fill']);
        Route::get('/templates/{key}/blank', [ArchitectTemplateController::class, 'downloadBlank']);
        Route::post('/templates/{key}/generate', [ArchitectTemplateController::class, 'generate']);

        Route::get('/licences/search', [ArchitectLicenceController::class, 'search']);
        Route::post('/licences', [ArchitectLicenceController::class, 'store']);

        Route::get('/stamper', function () {
            return redirect('/stamper');
        });
        Route::post('/stamper', function () {
            return redirect('/stamper');
        });
    });

    Route::middleware('pro:med,arch,eng')->prefix('pro/certificates')->group(function () {
        Route::get('/', [CertificateController::class, 'index']);
        Route::post('/lookup', [CertificateController::class, 'lookup']);
        Route::get('/create', [CertificateController::class, 'create']);
        Route::post('/', [CertificateController::class, 'store']);
        Route::get('/{certificate}/edit', [CertificateController::class, 'edit']);
        Route::put('/{certificate}', [CertificateController::class, 'update']);
        Route::post('/{certificate}/stamp', [CertificateController::class, 'stamp']);
        Route::get('/{certificate}/pdf', [CertificateController::class, 'downloadPdf']);
        Route::get('/{certificate}/photo', [CertificateController::class, 'downloadPhoto']);
    });

    Route::middleware('pro:eng')->prefix('pro/engineer')->group(function () {
        Route::get('/projects', [EngineerProjectController::class, 'index']);
        Route::get('/projects/create', [EngineerProjectController::class, 'create']);
        Route::post('/projects', [EngineerProjectController::class, 'store']);
        Route::get('/projects/{project}', [EngineerProjectController::class, 'show']);
        Route::get('/projects/{project}/edit', [EngineerProjectController::class, 'edit']);
        Route::put('/projects/{project}', [EngineerProjectController::class, 'update']);

        Route::get('/projects/{project}/pa/create', [EngineerPaController::class, 'create']);
        Route::post('/projects/{project}/pa', [EngineerPaController::class, 'store']);
        Route::get('/pa/{pa}', [EngineerPaController::class, 'show']);
        Route::get('/pa/{pa}/edit', [EngineerPaController::class, 'edit']);
        Route::put('/pa/{pa}', [EngineerPaController::class, 'update']);

        Route::get('/documents', [EngineerDocumentController::class, 'index']);
        Route::get('/documents/create', [EngineerDocumentController::class, 'create']);
        Route::post('/documents', [EngineerDocumentController::class, 'store']);
        Route::get('/documents/{document}', [EngineerDocumentController::class, 'show']);
        Route::put('/documents/{document}', [EngineerDocumentController::class, 'update']);
        Route::post('/documents/{document}/revisions', [EngineerDocumentController::class, 'uploadRevision']);
        Route::get('/documents/{document}/revisions/{revision}/download', [EngineerDocumentController::class, 'download']);

        Route::get('/certificates', [EngineerCertificateController::class, 'index']);
        Route::get('/certificates/create', [EngineerCertificateController::class, 'create']);
        Route::post('/certificates', [EngineerCertificateController::class, 'store']);
        Route::get('/certificates/{certificate}', [EngineerCertificateController::class, 'show']);
        Route::get('/certificates/{certificate}/edit', [EngineerCertificateController::class, 'edit']);
        Route::put('/certificates/{certificate}', [EngineerCertificateController::class, 'update']);
        Route::post('/certificates/{certificate}/stamp', [EngineerCertificateController::class, 'stamp']);
        Route::get('/certificates/{certificate}/pdf', [EngineerCertificateController::class, 'downloadPdf']);
        Route::get('/certificates/{certificate}/photos/{photo}', [EngineerCertificateController::class, 'downloadPhoto']);

        Route::get('/equipment', [EngineerEquipmentController::class, 'index']);
        Route::get('/equipment/due', [EngineerEquipmentController::class, 'due']);
        Route::get('/equipment/create', [EngineerEquipmentController::class, 'create']);
        Route::post('/equipment', [EngineerEquipmentController::class, 'store']);
        Route::get('/equipment/{id}', [EngineerEquipmentController::class, 'show'])->whereNumber('id');
        Route::get('/equipment/{id}/edit', [EngineerEquipmentController::class, 'edit'])->whereNumber('id');
        Route::put('/equipment/{id}', [EngineerEquipmentController::class, 'update'])->whereNumber('id');
        Route::get('/equipment/{id}/certificates/create', [EngineerEquipmentController::class, 'createCertificate'])->whereNumber('id');
        Route::post('/equipment/{id}/certificates', [EngineerEquipmentController::class, 'storeCertificate'])->whereNumber('id');
        Route::post('/equipment/{id}/renew', [EngineerEquipmentController::class, 'renew'])->whereNumber('id');
        Route::post('/equipment/{id}/rfp', [EngineerEquipmentController::class, 'createRfp'])->whereNumber('id');

        Route::get('/reports', [EngineerReportController::class, 'index']);
        Route::get('/reports/create', [EngineerReportController::class, 'create']);
        Route::post('/reports', [EngineerReportController::class, 'store']);
        Route::post('/reports/templates', [EngineerReportController::class, 'storeTemplate']);
        Route::get('/reports/{report}', [EngineerReportController::class, 'show']);
        Route::get('/reports/{report}/edit', [EngineerReportController::class, 'edit']);
        Route::put('/reports/{report}', [EngineerReportController::class, 'update']);
        Route::post('/reports/{report}/stamp', [EngineerReportController::class, 'stamp']);
        Route::get('/reports/{report}/pdf', [EngineerReportController::class, 'downloadPdf']);
        Route::get('/reports/{report}/photos/{photo}', [EngineerReportController::class, 'downloadPhoto']);

        Route::redirect('/certifications', '/pro/engineer/certificates');
        Route::redirect('/certifications/create', '/pro/engineer/certificates/create');
    });

    Route::middleware('company_books')->prefix('company')->group(function () {
        Route::get('/', [CompanyDeskController::class, 'index']);
        Route::get('/platform', [CompanyPlatformDashboardController::class, 'index']);
        Route::post('/platform/users/{id}/kpi-cohort', [CompanyPlatformDashboardController::class, 'setKpiCohort'])->whereNumber('id');
        Route::get('/profile', [CompanyProfileController::class, 'edit']);
        Route::put('/profile', [CompanyProfileController::class, 'update']);
        Route::post('/profile/logo', [CompanyProfileController::class, 'updateLogo']);
        Route::post('/profile/capital-received', [CompanyProfileController::class, 'markCapitalReceived']);

        Route::get('/clients', [CompanyClientController::class, 'index']);
        Route::get('/clients/create', [CompanyClientController::class, 'create']);
        Route::post('/clients', [CompanyClientController::class, 'store']);
        Route::get('/clients/{client}', [CompanyClientController::class, 'show']);
        Route::get('/clients/{client}/edit', [CompanyClientController::class, 'edit']);
        Route::put('/clients/{client}', [CompanyClientController::class, 'update']);

        Route::get('/invoices', [CompanyInvoiceController::class, 'index']);
        Route::get('/invoices/create', [CompanyInvoiceController::class, 'create']);
        Route::post('/invoices', [CompanyInvoiceController::class, 'store']);
        Route::post('/invoices/{document}/convert', [CompanyInvoiceController::class, 'convert']);
        Route::post('/invoices/{document}/pay', [CompanyInvoiceController::class, 'pay']);
        Route::post('/invoices/{document}/credit', [CompanyInvoiceController::class, 'credit']);
        Route::get('/invoices/{document}/pdf', [CompanyInvoiceController::class, 'pdf']);

        Route::get('/expenses', [CompanyExpenseController::class, 'index']);
        Route::get('/expenses/create', [CompanyExpenseController::class, 'create']);
        Route::post('/expenses', [CompanyExpenseController::class, 'store']);
        Route::post('/expenses/{expense}/refund', [CompanyExpenseController::class, 'markRefunded']);
        Route::get('/expenses/{expense}/receipt', [CompanyExpenseController::class, 'receipt']);

        Route::get('/accounts', [CompanyAccountsController::class, 'index']);
        Route::get('/accounts/chart', [CompanyAccountsController::class, 'chart']);
        Route::get('/accounts/journals', [CompanyAccountsController::class, 'journals']);
        Route::get('/accounts/customer-statement', [CompanyAccountsController::class, 'customerStatement']);
        Route::post('/accounts/lock', [CompanyAccountsController::class, 'lock']);
        Route::post('/accounts/unlock', [CompanyAccountsController::class, 'unlock']);

        Route::get('/bank', [CompanyBankController::class, 'index']);
        Route::post('/bank', [CompanyBankController::class, 'store']);
        Route::post('/bank/{line}/match', [CompanyBankController::class, 'match']);

        Route::get('/dividends', [CompanyDividendController::class, 'index']);
        Route::post('/dividends', [CompanyDividendController::class, 'store']);
        Route::post('/dividends/{dividend}/pay', [CompanyDividendController::class, 'pay']);

        Route::get('/recurring', [CompanyRecurringInvoiceController::class, 'index']);
        Route::post('/recurring', [CompanyRecurringInvoiceController::class, 'store']);
        Route::post('/recurring/generate', [CompanyRecurringInvoiceController::class, 'generateDue']);
        Route::post('/recurring/{schedule}/toggle', [CompanyRecurringInvoiceController::class, 'toggle']);

        Route::get('/beta-invites', [CompanyBetaInviteController::class, 'index']);
        Route::post('/beta-invites', [CompanyBetaInviteController::class, 'store']);
        Route::post('/beta-invites/{id}/revoke', [CompanyBetaInviteController::class, 'revoke'])->whereNumber('id');

        Route::get('/content', [CompanyContentStudioController::class, 'index']);

        Route::get('/promotions', [AdminPromotionController::class, 'index']);
        Route::get('/promotions/create', [AdminPromotionController::class, 'create']);
        Route::post('/promotions', [AdminPromotionController::class, 'store']);
        Route::get('/promotions/{promotion}/edit', [AdminPromotionController::class, 'edit']);
        Route::put('/promotions/{promotion}', [AdminPromotionController::class, 'update']);
        Route::post('/promotions/{promotion}/toggle', [AdminPromotionController::class, 'toggle']);
    });
});
