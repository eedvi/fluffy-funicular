<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Loan;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the loans to link payments
        $loans = Loan::all();

        $paymentCounter = 1;

        // Payment 1: Partial payment for Loan 1 (Active loan)
        Payment::create([
            'payment_number' => 'P-' . now()->format('Ymd') . '-' . str_pad($paymentCounter++, 4, '0', STR_PAD_LEFT),
            'loan_id' => 1, // Loan 1
            'amount' => 15000.00,
            'payment_method' => 'cash',
            'payment_date' => now()->subDays(10),
            'status' => 'completed',
            'notes' => 'Pago parcial en efectivo',
        ]);

        // Payment 2: Full payment for Loan 3 (Paid loan) - First installment
        Payment::create([
            'payment_number' => 'P-' . now()->format('Ymd') . '-' . str_pad($paymentCounter++, 4, '0', STR_PAD_LEFT),
            'loan_id' => 3, // Loan 3
            'amount' => 15000.00,
            'payment_method' => 'transfer',
            'payment_date' => now()->subDays(25),
            'status' => 'completed',
            'notes' => 'Primera cuota por transferencia bancaria',
        ]);

        // Payment 3: Full payment for Loan 3 (Paid loan) - Final payment
        Payment::create([
            'payment_number' => 'P-' . now()->format('Ymd') . '-' . str_pad($paymentCounter++, 4, '0', STR_PAD_LEFT),
            'loan_id' => 3, // Loan 3
            'amount' => 12000.00,
            'payment_method' => 'cash',
            'payment_date' => now()->subDays(5),
            'status' => 'completed',
            'notes' => 'Pago final en efectivo - préstamo saldado',
        ]);

        // Payment 4: Partial payment for Loan 4 (Overdue loan)
        Payment::create([
            'payment_number' => 'P-' . now()->format('Ymd') . '-' . str_pad($paymentCounter++, 4, '0', STR_PAD_LEFT),
            'loan_id' => 4, // Loan 4
            'amount' => 10000.00,
            'payment_method' => 'card',
            'payment_date' => now()->subDays(50),
            'status' => 'completed',
            'notes' => 'Pago parcial con tarjeta de débito',
        ]);

        // Payment 5: Small payment for Loan 5 (Confiscated loan)
        Payment::create([
            'payment_number' => 'P-' . now()->format('Ymd') . '-' . str_pad($paymentCounter++, 4, '0', STR_PAD_LEFT),
            'loan_id' => 5, // Loan 5
            'amount' => 5000.00,
            'payment_method' => 'cash',
            'payment_date' => now()->subDays(100),
            'status' => 'completed',
            'notes' => 'Único pago realizado antes de la confiscación',
        ]);

        // Payment 6: Pending payment for Loan 1
        Payment::create([
            'payment_number' => 'P-' . now()->format('Ymd') . '-' . str_pad($paymentCounter++, 4, '0', STR_PAD_LEFT),
            'loan_id' => 1, // Loan 1
            'amount' => 20000.00,
            'payment_method' => 'transfer',
            'payment_date' => now(),
            'status' => 'pending',
            'notes' => 'Pago pendiente de confirmación',
        ]);
    }
}
