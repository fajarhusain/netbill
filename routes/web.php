<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashSettlementController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CollectionVisitController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DusunController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptStorageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaBlastController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/bukti/{token}', [PaymentController::class, 'publicReceiptImage'])
    ->middleware('throttle:60,1')->name('payments.receipt.public');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Scan — all authenticated users
    Route::prefix('scan')->name('scan.')->group(function () {
        Route::get('/', [ScanController::class, 'index'])->name('index');
        Route::post('/lookup', [ScanController::class, 'lookup'])->name('lookup');
        Route::get('/customer/{customer}', [ScanController::class, 'show'])->name('customer');
        Route::get('/customer/{customer}/pay', [ScanController::class, 'payForm'])->name('pay.form');
        Route::post('/customer/{customer}/pay', [ScanController::class, 'payStore'])->name('pay.store');
    });

    // Payments history — shared
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        Route::get('/{payment}/receipt', [PaymentController::class, 'receipt'])->name('receipt');
        Route::get('/{payment}/pdf', [PaymentController::class, 'pdf'])->name('pdf');
        Route::post('/{payment}/send-receipt', [PaymentController::class, 'sendReceipt'])->name('send-receipt');
        Route::get('/{payment}/receipt-image', [PaymentController::class, 'receiptImage'])->name('receipt.image');
    });

    // Visits — shared
    Route::resource('visits', CollectionVisitController::class)->except(['show']);
    Route::get('/visits/{visit}', [CollectionVisitController::class, 'show'])->name('visits.show');

    // Settlements — shared (collector sees own; admin sees all)
    Route::resource('settlements', CashSettlementController::class)->except(['show']);
    Route::get('/settlements/{settlement}', [CashSettlementController::class, 'show'])->name('settlements.show');
    Route::post('/settlements/{settlement}/confirm', [CashSettlementController::class, 'confirm'])
        ->middleware('role:admin')->name('settlements.confirm');

    // WhatsApp blast penagihan — shared
    Route::prefix('blast')->name('blast.')->group(function () {
        Route::get('/', [WaBlastController::class, 'index'])->name('index');
        Route::post('/log', [WaBlastController::class, 'log'])->name('log');
        Route::get('/history', [WaBlastController::class, 'history'])->name('history');
    });

    // Collections — shared (collect by dusun)
    Route::prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::get('/{dusun}', [CollectionController::class, 'show'])->name('show');
    });
});

// Admin-only routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/customers/by-dusun', [CustomerController::class, 'byDusun'])->name('customers.by-dusun');
    Route::get('/customers/import/template', [CustomerController::class, 'importTemplate'])->name('customers.import.template');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    Route::resource('customers', CustomerController::class);
    Route::post('/customers/{customer}/regenerate-barcode', [CustomerController::class, 'regenerateBarcode'])
        ->name('customers.regenerate');
    Route::get('/customers/{customer}/card', [CustomerController::class, 'card'])->name('customers.card');
    Route::get('/customers/{customer}/qrcode', [CustomerController::class, 'qrcode'])->name('customers.qrcode');

    Route::resource('packages', PackageController::class)->except(['show']);
    Route::get('/packages/{package}', [PackageController::class, 'show'])->name('packages.show');

    Route::resource('dusun', DusunController::class)->except(['show']);

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
    });

    Route::resource('users', UserController::class)->except(['show']);
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::post('/payments/{payment}/reset-receipt', [ReceiptStorageController::class, 'resetPayment'])->name('payments.reset-receipt');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    Route::prefix('receipts/storage')->name('receipts.')->group(function () {
        Route::get('/', [ReceiptStorageController::class, 'index'])->name('storage');
        Route::post('/reset', [ReceiptStorageController::class, 'reset'])->name('storage.reset');
        Route::post('/regenerate', [ReceiptStorageController::class, 'regenerate'])->name('storage.regenerate');
    });
});
