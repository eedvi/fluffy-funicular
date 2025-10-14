<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfController extends Controller
{
    /**
     * Generate loan contract PDF
     */
    public function loanContract(Loan $loan): Response
    {
        $loan->load(['customer', 'item.category', 'branch']);

        $pdf = Pdf::loadView('pdf.loan-contract', compact('loan'));

        return $pdf->stream('contrato-prestamo-' . $loan->loan_number . '.pdf');
    }

    /**
     * Download loan contract PDF
     */
    public function downloadLoanContract(Loan $loan): Response
    {
        $loan->load(['customer', 'item.category', 'branch']);

        $pdf = Pdf::loadView('pdf.loan-contract', compact('loan'));

        return $pdf->download('contrato-prestamo-' . $loan->loan_number . '.pdf');
    }

    /**
     * Generate loan receipt PDF
     */
    public function loanReceipt(Loan $loan): Response
    {
        $loan->load(['customer', 'item', 'branch']);
        $branch = $loan->branch;

        $pdf = Pdf::loadView('pdf.loan-receipt', compact('loan', 'branch'));

        return $pdf->stream('comprobante-prestamo-' . $loan->loan_number . '.pdf');
    }

    /**
     * Download loan receipt PDF
     */
    public function downloadLoanReceipt(Loan $loan): Response
    {
        $loan->load(['customer', 'item', 'branch']);
        $branch = $loan->branch;

        $pdf = Pdf::loadView('pdf.loan-receipt', compact('loan', 'branch'));

        return $pdf->download('comprobante-prestamo-' . $loan->loan_number . '.pdf');
    }

    /**
     * Generate payment receipt PDF
     */
    public function paymentReceipt(Payment $payment): Response
    {
        $payment->load(['loan.customer', 'loan.item', 'branch']);
        // Refresh loan to get updated amount_paid and balance_remaining
        $payment->loan->refresh();
        $branch = $payment->branch;

        $pdf = Pdf::loadView('pdf.payment-receipt', compact('payment', 'branch'));

        return $pdf->stream('recibo-pago-' . $payment->payment_number . '.pdf');
    }

    /**
     * Download payment receipt PDF
     */
    public function downloadPaymentReceipt(Payment $payment): Response
    {
        $payment->load(['loan.customer', 'loan.item', 'branch']);
        // Refresh loan to get updated amount_paid and balance_remaining
        $payment->loan->refresh();
        $branch = $payment->branch;

        $pdf = Pdf::loadView('pdf.payment-receipt', compact('payment', 'branch'));

        return $pdf->download('recibo-pago-' . $payment->payment_number . '.pdf');
    }

    /**
     * Generate sale receipt PDF
     */
    public function saleReceipt(Sale $sale): Response
    {
        $sale->load(['customer', 'item', 'branch']);
        $branch = $sale->branch;

        $pdf = Pdf::loadView('pdf.sale-receipt', compact('sale', 'branch'));

        return $pdf->stream('comprobante-venta-' . $sale->sale_number . '.pdf');
    }

    /**
     * Download sale receipt PDF
     */
    public function downloadSaleReceipt(Sale $sale): Response
    {
        $sale->load(['customer', 'item', 'branch']);
        $branch = $sale->branch;

        $pdf = Pdf::loadView('pdf.sale-receipt', compact('sale', 'branch'));

        return $pdf->download('comprobante-venta-' . $sale->sale_number . '.pdf');
    }
}
