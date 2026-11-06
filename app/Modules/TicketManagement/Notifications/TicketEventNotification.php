<?php

namespace App\Modules\TicketManagement\Notifications;

use App\Modules\TicketManagement\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ticket {$this->ticket->ticket_number}: {$this->event}")
            ->line("Ticket: {$this->ticket->subject}")
            ->line($this->event)
            ->action('View ticket', route('tickets.show', $this->ticket));
    }
}
