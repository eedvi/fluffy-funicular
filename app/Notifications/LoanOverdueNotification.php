<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Loan $loan,
        public int $daysOverdue,
        public float $interestCharged
    ) {}

    public function via($notifiable): array
    {
        $channels = ['mail'];

        // Add database notification for staff
        if ($notifiable instanceof \App\Models\User) {
            $channels[] = 'database';
        }

        // Add SMS/WhatsApp for customers (if configured)
        if ($notifiable instanceof \App\Models\Customer) {
            // Uncomment when you configure Twilio/WhatsApp
            // $channels[] = \App\Channels\SmsChannel::class;
            // $channels[] = \App\Channels\WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toSms($notifiable): string
    {
        return "URGENTE: Su préstamo {$this->loan->loan_number} está vencido por {$this->daysOverdue} día(s). Se aplicó un interés de $" . number_format($this->interestCharged, 2) . ". Por favor, comuníquese con nosotros.";
    }

    public function toWhatsApp($notifiable): string
    {
        return "🔴 *PRÉSTAMO VENCIDO*\n\n" .
               "Su préstamo *{$this->loan->loan_number}* está vencido por *{$this->daysOverdue} día(s)*.\n\n" .
               "💰 Interés aplicado hoy: $" . number_format($this->interestCharged, 2) . "\n" .
               "💳 Saldo pendiente: $" . number_format($this->loan->balance_remaining, 2) . "\n\n" .
               "⚠️ Se están aplicando cargos diarios. Por favor, comuníquese con nosotros lo antes posible.\n\n" .
               "Casa de Empeño";
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('URGENTE: Préstamo Vencido - Acción Requerida')
            ->greeting('Estimado/a ' . $this->loan->customer->first_name)
            ->error()
            ->line("Su préstamo **{$this->loan->loan_number}** está **vencido por {$this->daysOverdue} día(s)**.")
            ->line("**Fecha de vencimiento:** " . $this->loan->due_date->format('d/m/Y'))
            ->line("**Saldo pendiente:** $" . number_format($this->loan->balance_remaining, 2))
            ->line("**Interés aplicado hoy:** $" . number_format($this->interestCharged, 2))
            ->line('⚠️ **IMPORTANTE:** Se están aplicando cargos por intereses diarios. Su saldo está aumentando cada día.')
            ->line('Por favor, comuníquese con nosotros lo antes posible para regularizar su situación y evitar más cargos.')
            ->action('Contactar', url('/'))
            ->line('Estamos aquí para ayudarle a encontrar una solución.')
            ->salutation('Atentamente, Casa de Empeño');
    }

    public function toArray($notifiable): array
    {
        return [
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'days_overdue' => $this->daysOverdue,
            'interest_charged' => $this->interestCharged,
            'customer_name' => $this->loan->customer->full_name,
        ];
    }
}
