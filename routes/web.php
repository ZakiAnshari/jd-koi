<?php

use App\Http\Controllers\UsersController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\BreedersController;
use App\Http\Controllers\KoisController;
use App\Http\Controllers\StocksController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('users', [UsersController::class, 'index'])->name('users.index');
    Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
    Route::post('users', [UsersController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UsersController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');

    Route::get('master-data/breeder', [BreedersController::class, 'index'])->name('breeders.index');
    Route::get('master-data/breeder/create', [BreedersController::class, 'create'])->name('breeders.create');
    Route::post('master-data/breeder', [BreedersController::class, 'store'])->name('breeders.store');
    Route::get('master-data/breeder/{breeder}', [BreedersController::class, 'show'])->name('breeders.show');
    Route::get('master-data/breeder/{breeder}/edit', [BreedersController::class, 'edit'])->name('breeders.edit');
    Route::put('master-data/breeder/{breeder}', [BreedersController::class, 'update'])->name('breeders.update');
    Route::delete('master-data/breeder/{breeder}', [BreedersController::class, 'destroy'])->name('breeders.destroy');

    Route::get('master-data/koi', [KoisController::class, 'index'])->name('kois.index');
    Route::get('master-data/koi/create', [KoisController::class, 'create'])->name('kois.create');
    Route::post('master-data/koi', [KoisController::class, 'store'])->name('kois.store');
    Route::get('master-data/koi/{koi}', [KoisController::class, 'show'])->name('kois.show');
    Route::get('master-data/koi/{koi}/edit', [KoisController::class, 'edit'])->name('kois.edit');
    Route::put('master-data/koi/{koi}', [KoisController::class, 'update'])->name('kois.update');
    Route::delete('master-data/koi/{koi}', [KoisController::class, 'destroy'])->name('kois.destroy');

    Route::get('inventory', [StocksController::class, 'index'])->name('stocks.index');
    Route::get('inventory/create', [StocksController::class, 'create'])->name('stocks.create');
    Route::post('inventory', [StocksController::class, 'store'])->name('stocks.store');
    Route::get('inventory/{stock}', [StocksController::class, 'show'])->name('stocks.show');
    Route::get('inventory/{stock}/edit', [StocksController::class, 'edit'])->name('stocks.edit');
    Route::put('inventory/{stock}', [StocksController::class, 'update'])->name('stocks.update');
    Route::delete('inventory/{stock}', [StocksController::class, 'destroy'])->name('stocks.destroy');

    Route::get('sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('sales/create', [SalesController::class, 'create'])->name('sales.create');
    Route::post('sales', [SalesController::class, 'store'])->name('sales.store');
    Route::get('sales/{salesReport}', [SalesController::class, 'show'])->name('sales.show');
    Route::get('sales/{salesReport}/edit', [SalesController::class, 'edit'])->name('sales.edit');
    Route::put('sales/{salesReport}', [SalesController::class, 'update'])->name('sales.update');
    Route::delete('sales/{salesReport}', [SalesController::class, 'destroy'])->name('sales.destroy');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
});

require __DIR__.'/settings.php';
