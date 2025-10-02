<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    /**
     * Generate loan contract PDF
     *
     * @param Loan $loan
     * @return \Illuminate\Http\Response
     */
    public function generateLoanContract(Loan $loan)
    {
        $pdf = Pdf::loadView('pdf.loan-contract', compact('loan'));

        return $pdf->download("contrato-prestamo-{$loan->loan_number}.pdf");
    }

    /**
     * Generate payment receipt PDF
     *
     * @param Payment $payment
     * @return \Illuminate\Http\Response
     */
    public function generatePaymentReceipt(Payment $payment)
    {
        $pdf = Pdf::loadView('pdf.payment-receipt', compact('payment'));

        return $pdf->download("recibo-pago-{$payment->payment_number}.pdf");
    }

    /**
     * Generate sale invoice PDF
     *
     * @param Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function generateSaleInvoice(Sale $sale)
    {
        $pdf = Pdf::loadView('pdf.sale-invoice', compact('sale'));

        return $pdf->download("factura-venta-{$sale->sale_number}.pdf");
    }

    /**
     * Stream loan contract PDF (for preview)
     *
     * @param Loan $loan
     * @return \Illuminate\Http\Response
     */
    public function streamLoanContract(Loan $loan)
    {
        $pdf = Pdf::loadView('pdf.loan-contract', compact('loan'));

        return $pdf->stream("contrato-prestamo-{$loan->loan_number}.pdf");
    }

    /**
     * Stream payment receipt PDF (for preview)
     *
     * @param Payment $payment
     * @return \Illuminate\Http\Response
     */
    public function streamPaymentReceipt(Payment $payment)
    {
        $pdf = Pdf::loadView('pdf.payment-receipt', compact('payment'));

        return $pdf->stream("recibo-pago-{$payment->payment_number}.pdf");
    }

    /**
     * Stream sale invoice PDF (for preview)
     *
     * @param Sale $sale
     * @return \Illuminate\Http\Response
     */
    public function streamSaleInvoice(Sale $sale)
    {
        $pdf = Pdf::loadView('pdf.sale-invoice', compact('sale'));

        return $pdf->stream("factura-venta-{$sale->sale_number}.pdf");
    }
}
