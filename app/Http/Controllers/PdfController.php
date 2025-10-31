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
     * Log PDF generation activity
     */
    protected function logPdfGeneration(string $pdfType, $model, string $action = 'view'): void
    {
        activity()
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->withProperties([
                'pdf_type' => $pdfType,
                'action' => $action,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("PDF generado: {$pdfType} ({$action})");
    }

    /**
     * Generate loan contract PDF
     */
    public function loanContract(Loan $loan): Response
    {
        $loan->load(['customer', 'item.category', 'branch']);
        $this->logPdfGeneration('Contrato de Préstamo', $loan, 'view');

        $pdf = Pdf::loadView('pdf.loan-contract', compact('loan'));

        return $pdf->stream('contrato-prestamo-' . $loan->loan_number . '.pdf');
    }

    /**
     * Download loan contract PDF
     */
    public function downloadLoanContract(Loan $loan): Response
    {
        $loan->load(['customer', 'item.category', 'branch']);
        $this->logPdfGeneration('Contrato de Préstamo', $loan, 'download');

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
        $this->logPdfGeneration('Comprobante de Préstamo', $loan, 'view');

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
        $this->logPdfGeneration('Comprobante de Préstamo', $loan, 'download');

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
        $this->logPdfGeneration('Recibo de Pago', $payment, 'view');

        $pdf = Pdf::loadView('pdf.payment-receipt-ticket', compact('payment', 'branch'))
            ->setPaper([0, 0, 226.77, 566.93], 'portrait'); // 80mm x 200mm (ticket format)

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
        $this->logPdfGeneration('Recibo de Pago', $payment, 'download');

        $pdf = Pdf::loadView('pdf.payment-receipt-ticket', compact('payment', 'branch'))
            ->setPaper([0, 0, 226.77, 566.93], 'portrait'); // 80mm x 200mm (ticket format)

        return $pdf->download('recibo-pago-' . $payment->payment_number . '.pdf');
    }

    /**
     * Generate sale receipt PDF
     */
    public function saleReceipt(Sale $sale): Response
    {
        $sale->load(['customer', 'item', 'branch']);
        $branch = $sale->branch;
        $this->logPdfGeneration('Comprobante de Venta', $sale, 'view');

        $pdf = Pdf::loadView('pdf.sale-receipt-ticket', compact('sale', 'branch'))
            ->setPaper([0, 0, 226.77, 566.93], 'portrait'); // 80mm x 200mm (ticket format)

        return $pdf->stream('comprobante-venta-' . $sale->sale_number . '.pdf');
    }

    /**
     * Download sale receipt PDF
     */
    public function downloadSaleReceipt(Sale $sale): Response
    {
        $sale->load(['customer', 'item', 'branch']);
        $branch = $sale->branch;
        $this->logPdfGeneration('Comprobante de Venta', $sale, 'download');

        $pdf = Pdf::loadView('pdf.sale-receipt-ticket', compact('sale', 'branch'))
            ->setPaper([0, 0, 226.77, 566.93], 'portrait'); // 80mm x 200mm (ticket format)

        return $pdf->download('comprobante-venta-' . $sale->sale_number . '.pdf');
    }
}
