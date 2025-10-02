<?php

use Illuminate\Support\Facades\Route;
use App\Services\PdfService;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;

Route::get('/', function () {
    return view('welcome');
});

// PDF Download Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/pdf/loan-contract/{loan}', function (Loan $loan) {
        $pdfService = new PdfService();
        return $pdfService->generateLoanContract($loan);
    })->name('pdf.loan-contract');

    Route::get('/pdf/payment-receipt/{payment}', function (Payment $payment) {
        $pdfService = new PdfService();
        return $pdfService->generatePaymentReceipt($payment);
    })->name('pdf.payment-receipt');

    Route::get('/pdf/sale-invoice/{sale}', function (Sale $sale) {
        $pdfService = new PdfService();
        return $pdfService->generateSaleInvoice($sale);
    })->name('pdf.sale-invoice');
});
