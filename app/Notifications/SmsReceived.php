<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmsReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Order $order)
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
        return (new MailMessage)
            ->subject('Code SMS reçu — Zen_Sms')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line("Un code SMS est arrivé pour votre numéro {$this->order->phone} ({$this->order->service}).")
            ->line("Code reçu : {$this->order->sms_code}")
            ->action('Voir ma commande', route('purchase.waiting', $this->order))
            ->line('Merci de votre confiance.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sms_received',
            'message' => "Code SMS reçu pour {$this->order->phone} ({$this->order->service}).",
            'order_id' => $this->order->id,
            'sms_code' => $this->order->sms_code,
        ];
    }
}
