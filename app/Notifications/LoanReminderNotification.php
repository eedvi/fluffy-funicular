<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Loan $loan,
        public int $daysUntilDue
    ) {}

    public function via($notifiable): array
    {
        $channels = ['mail'];

        // Add database notification for staff
        if ($notifiable instanceof \App\Models\User) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recordatorio: Su préstamo vence pronto')
            ->greeting('Estimado/a ' . $this->loan->customer->first_name)
            ->line("Le recordamos que su préstamo **{$this->loan->loan_number}** vence en **{$this->daysUntilDue} días**.")
            ->line("**Fecha de vencimiento:** " . $this->loan->due_date->format('d/m/Y'))
            ->line("**Monto del préstamo:** $" . number_format($this->loan->loan_amount, 2))
            ->line("**Saldo pendiente:** $" . number_format($this->loan->balance_remaining, 2))
            ->line('Por favor, asegúrese de realizar su pago antes de la fecha de vencimiento para evitar cargos adicionales.')
            ->action('Ver Detalles', url('/'))
            ->line('Gracias por su confianza.')
            ->salutation('Saludos, Casa de Empeño');
    }

    public function toArray($notifiable): array
    {
        return [
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'days_until_due' => $this->daysUntilDue,
            'customer_name' => $this->loan->customer->full_name,
        ];
    }
}
