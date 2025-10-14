<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;

Route::get('/', function () {
    return view('welcome');
});

// PDF Routes
Route::middleware(['auth'])->group(function () {
    // Loan PDFs
    Route::get('/pdf/loan-contract/{loan}', [PdfController::class, 'loanContract'])->name('pdf.loan-contract');
    Route::get('/pdf/loan-contract/{loan}/download', [PdfController::class, 'downloadLoanContract'])->name('pdf.loan-contract.download');
    Route::get('/pdf/loan-receipt/{loan}', [PdfController::class, 'loanReceipt'])->name('pdf.loan-receipt');
    Route::get('/pdf/loan-receipt/{loan}/download', [PdfController::class, 'downloadLoanReceipt'])->name('pdf.loan-receipt.download');

    // Payment PDFs
    Route::get('/pdf/payment-receipt/{payment}', [PdfController::class, 'paymentReceipt'])->name('pdf.payment-receipt');
    Route::get('/pdf/payment-receipt/{payment}/download', [PdfController::class, 'downloadPaymentReceipt'])->name('pdf.payment-receipt.download');

    // Sale PDFs
    Route::get('/pdf/sale-receipt/{sale}', [PdfController::class, 'saleReceipt'])->name('pdf.sale-receipt');
    Route::get('/pdf/sale-receipt/{sale}/download', [PdfController::class, 'downloadSaleReceipt'])->name('pdf.sale-receipt.download');
});
