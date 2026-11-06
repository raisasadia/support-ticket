<?php

namespace App\Modules\TicketManagement\Commands;

use App\Modules\TicketManagement\Models\Ticket;
use App\Modules\TicketManagement\Notifications\TicketEventNotification;
use Illuminate\Console\Command;

class EscalateTicketsCommand extends Command
{
    protected $signature = 'tickets:escalate';
    protected $description = 'Mark approaching and breached tickets and notify responsible users';

    public function handle(): int
    {
        Ticket::with(['creator', 'agent'])
            ->whereIn('status', ['open', 'in_progress', 'waiting_customer'])
            ->whereNotNull('due_at')
            ->whereNull('sla_escalated_at')
            ->where('due_at', '<=', now()->addHours(2))
            ->chunkById(100, function ($tickets): void {
                foreach ($tickets as $ticket) {
                    $ticket->update(['sla_escalated_at' => now()]);
                    $ticket->activities()->create([
                        'event' => 'sla_escalated',
                        'description' => $ticket->due_at->isPast() ? 'SLA breached.' : 'SLA approaching.',
                    ]);
                    $recipient = $ticket->agent ?: $ticket->creator;
                    $recipient?->notify(new TicketEventNotification($ticket, $ticket->due_at->isPast() ? 'SLA breached' : 'SLA approaching'));
                }
            });

        return self::SUCCESS;
    }
}
