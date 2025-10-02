<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $loan = $this->payment->loan;

        return (new MailMessage)
            ->subject('Confirmación de Pago Recibido')
            ->greeting('Estimado/a ' . $loan->customer->first_name)
            ->line('¡Gracias! Hemos recibido su pago exitosamente.')
            ->line("**Número de pago:** {$this->payment->payment_number}")
            ->line("**Préstamo:** {$loan->loan_number}")
            ->line("**Monto pagado:** $" . number_format($this->payment->amount, 2))
            ->line("**Fecha de pago:** " . $this->payment->payment_date->format('d/m/Y'))
            ->line("**Método de pago:** {$this->payment->payment_method}")
            ->line("**Saldo restante del préstamo:** $" . number_format($loan->balance_remaining, 2))
            ->when($loan->balance_remaining > 0, function ($mail) use ($loan) {
                return $mail->line("**Próximo vencimiento:** " . $loan->due_date->format('d/m/Y'));
            })
            ->when($loan->balance_remaining <= 0, function ($mail) {
                return $mail->line('🎉 **¡Su préstamo ha sido liquidado completamente!**');
            })
            ->action('Ver Recibo', route('pdf.payment-receipt', $this->payment))
            ->line('Gracias por su pago puntual.')
            ->salutation('Saludos, Casa de Empeño');
    }

    public function toArray($notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'amount' => $this->payment->amount,
            'loan_number' => $this->payment->loan->loan_number,
        ];
    }
}
