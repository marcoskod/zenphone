<?php

namespace App\Notifications;

use App\Models\Topup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TopupConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Topup $topup)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format((float) $this->topup->amount_fcfa, 0, ',', ' ');
        $balance = number_format((float) $notifiable->balance, 0, ',', ' ');

        return (new MailMessage)
            ->subject('Rechargement confirmé — Zen_Sms')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Votre rechargement de {$amount} FCFA a été confirmé.")
            ->line("Votre nouveau solde est de {$balance} FCFA.")
            ->action('Voir mon compte', url('/'))
            ->line('Merci de votre confiance.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $amount = number_format((float) $this->topup->amount_fcfa, 0, ',', ' ');

        return [
            'type' => 'topup_confirmed',
            'message' => "Rechargement de {$amount} FCFA confirmé.",
            'topup_id' => $this->topup->id,
            'amount_fcfa' => (float) $this->topup->amount_fcfa,
        ];
    }
}
