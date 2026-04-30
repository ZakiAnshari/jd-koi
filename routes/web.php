<?php

use App\Http\Controllers\UsersController;
use App\Http\Controllers\BreedersController;
use App\Http\Controllers\JenisKoisController;
use App\Http\Controllers\KoisController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\PurchasesController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\StocksController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RekapTransaksiController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('customer/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
    Route::get('rekap-transaksi', [RekapTransaksiController::class, 'index'])->name('rekap-transaksi.index');
    Route::get('rekap-transaksi/export-pdf', [RekapTransaksiController::class, 'exportPdf'])->name('rekap-transaksi.export-pdf');
    Route::get('rekap-transaksi/invoice-pdf', [RekapTransaksiController::class, 'exportInvoicePdf'])->name('rekap-transaksi.invoice-pdf');
    Route::get('rekap-transaksi/{koi}', [RekapTransaksiController::class, 'show'])->name('rekap-transaksi.show');

    // Customer Profile
    Route::get('customer/profile', [CustomerProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('customer/profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
    Route::put('customer/profile/password', [CustomerProfileController::class, 'updatePassword'])->name('customer.profile.password');
});

// Admin-Only Routes
Route::middleware(['auth', 'verified', 'ensure.admin'])->group(function () {
    // USERS - Admin Only
    Route::get('users', [UsersController::class, 'index'])->name('users.index');
    Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
    Route::post('users', [UsersController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UsersController::class, 'show'])->name('users.show');
    // Route::get('users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    // Route::put('users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    //JENIS KOI
    Route::get('master-data/jenis-koi', [JenisKoisController::class, 'index'])->name('jenis-kois.index');
    Route::post('master-data/jenis-koi', [JenisKoisController::class, 'store'])->name('jenis-kois.store');
    Route::put('master-data/jenis-koi/{jenisKoi}', [JenisKoisController::class, 'update'])->name('jenis-kois.update');
    Route::delete('master-data/jenis-koi/{jenisKoi}', [JenisKoisController::class, 'destroy'])->name('jenis-kois.destroy');
    //BREEDER
    Route::get('master-data/breeder', [BreedersController::class, 'index'])->name('breeders.index');
    Route::get('master-data/breeder/create', [BreedersController::class, 'create'])->name('breeders.create');
    Route::post('master-data/breeder', [BreedersController::class, 'store'])->name('breeders.store');
    Route::get('master-data/breeder/{breeder}', [BreedersController::class, 'show'])->name('breeders.show');
    Route::get('master-data/breeder/{breeder}/edit', [BreedersController::class, 'edit'])->name('breeders.edit');
    Route::put('master-data/breeder/{breeder}', [BreedersController::class, 'update'])->name('breeders.update');
    Route::delete('master-data/breeder/{breeder}', [BreedersController::class, 'destroy'])->name('breeders.destroy');
    //KOI
    Route::get('master-data/koi', [KoisController::class, 'index'])->name('kois.index');
    Route::get('master-data/koi/create', [KoisController::class, 'create'])->name('kois.create');
    Route::post('master-data/koi', [KoisController::class, 'store'])->name('kois.store');
    Route::get('master-data/koi/{koi}', [KoisController::class, 'show'])->name('kois.show');
    Route::get('master-data/koi/{koi}/edit', [KoisController::class, 'edit'])->name('kois.edit');
    Route::put('master-data/koi/{koi}', [KoisController::class, 'update'])->name('kois.update');
    Route::delete('master-data/koi/{koi}', [KoisController::class, 'destroy'])->name('kois.destroy');
    Route::put('master-data/koi/{koi}/status', [KoisController::class, 'updateStatus'])->name('kois.updateStatus');
    Route::delete('master-data/koi/{koi}/images/{koiImage}', [KoisController::class, 'deleteImage'])->name('kois.images.destroy');
    //CUSTUMER
    Route::get('master-data/customer', [CustomersController::class, 'index'])->name('customers.index');
    Route::get('master-data/customer/create', [CustomersController::class, 'create'])->name('customers.create');
    Route::post('master-data/customer', [CustomersController::class, 'store'])->name('customers.store');
    Route::get('master-data/customer/{customer}', [CustomersController::class, 'show'])->name('customers.show');
    Route::get('master-data/customer/{customer}/edit', [CustomersController::class, 'edit'])->name('customers.edit');
    Route::put('master-data/customer/{customer}', [CustomersController::class, 'update'])->name('customers.update');
    Route::delete('master-data/customer/{customer}', [CustomersController::class, 'destroy'])->name('customers.destroy');
    //PEMBELIAN
    Route::get('pembelian', [PurchasesController::class, 'index'])->name('purchases.index');
    Route::get('pembelian/print', [PurchasesController::class, 'print'])->name('purchases.print');
    Route::post('pembelian', [PurchasesController::class, 'store'])->name('purchases.store');
    Route::get('pembelian/{purchase}', [PurchasesController::class, 'show'])->name('purchases.show');
    Route::get('pembelian/{purchase}/edit', [PurchasesController::class, 'edit'])->name('purchases.edit');
    Route::put('pembelian/{purchase}', [PurchasesController::class, 'update'])->name('purchases.update');
    Route::delete('pembelian/{purchase}', [PurchasesController::class, 'destroy'])->name('purchases.destroy');
    // STOK KOI
    Route::get('inventory', [StocksController::class, 'index'])->name('stocks.index');
    Route::post('inventory', [StocksController::class, 'store'])->name('stocks.store');
    Route::get('inventory/{stock}', [StocksController::class, 'show'])->name('stocks.show');
    Route::get('inventory/{stock}/edit', [StocksController::class, 'edit'])->name('stocks.edit');
    Route::put('inventory/{stock}', [StocksController::class, 'update'])->name('stocks.update');
    Route::delete('inventory/{stock}', [StocksController::class, 'destroy'])->name('stocks.destroy');
    // PENJUALAN
    Route::get('penjualan', [TransactionController::class, 'index'])->name('penjualan.index');
    Route::post('penjualan', [TransactionController::class, 'store'])->name('penjualan.store');
    Route::get('penjualan/{transaction}', [TransactionController::class, 'show'])->name('penjualan.show');
    Route::get('penjualan/{transaction}/edit', [TransactionController::class, 'edit'])->name('penjualan.edit');
    Route::put('penjualan/{transaction}', [TransactionController::class, 'update'])->name('penjualan.update');
    Route::delete('penjualan/{transaction}', [TransactionController::class, 'destroy'])->name('penjualan.destroy');
    // LOG AKTIVITAS
    Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log-aktivitas.index');
    Route::get('/log-aktivitas/{id}', [ActivityLogController::class, 'show'])->name('log-aktivitas.show');
    Route::delete('/log-aktivitas/clear', [ActivityLogController::class, 'destroy'])->name('log-aktivitas.clear');

    Route::prefix('admin')->name('admin.')->group(function () {
        // Hak Akses
        Route::get('hak-akses/admin', [UsersController::class, 'hakAkses'])->defaults('role', 'admin')->name('hak-akses.admin');
        Route::get('hak-akses/admin/{user}/edit', [UsersController::class, 'editAdmin'])->name('hak-akses.admin.edit');
        Route::put('hak-akses/admin/{user}', [UsersController::class, 'updateAdmin'])->name('hak-akses.admin.update');
        Route::get('hak-akses/customer', [UsersController::class, 'hakAkses'])->defaults('role', 'customer')->name('hak-akses.customer');
        Route::post('hak-akses/customer', [UsersController::class, 'storeCustomer'])->name('hak-akses.customer.store');
        Route::get('hak-akses/customer/export/excel', [UsersController::class, 'exportCustomerExcel'])->name('hak-akses.customer.export-excel');
        Route::get('hak-akses/customer/{user}', [UsersController::class, 'showCustomer'])->name('hak-akses.customer.show');
    });
});

require __DIR__.'/settings.php';
