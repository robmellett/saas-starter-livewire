<?php

use App\Http\Controllers\Billing\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/billing', 'billing.index')->name('billing');
    Route::view('/billing/pending', 'billing.pending')->name('billing.pending');
    Route::get('/billing/payment-method', PaymentMethodController::class)->name('billing.payment-method');
});
