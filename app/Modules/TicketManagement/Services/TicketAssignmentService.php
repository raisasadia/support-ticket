<?php

namespace App\Modules\TicketManagement\Services;

use App\Models\User;
use App\Modules\TicketManagement\Models\Ticket;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketAssignmentService
{
    public function autoAssign(Ticket $ticket, ?User $actor = null): ?Ticket
    {
        return DB::transaction(function () use ($ticket, $actor) {
            $agent = User::query()
                ->whereHas('roleDefinition', fn ($query) => $query->where('role_name', 'agent'))
                ->where('is_active', true)
                ->withCount(['assignedTickets as open_ticket_count' => fn ($query) => $query->whereIn('status', ['open', 'in_progress', 'waiting_customer'])])
                ->orderBy('open_ticket_count')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$agent) {
                return null;
            }

            $updated = Ticket::whereKey($ticket->id)
                ->whereNull('assigned_to')
                ->update(['assigned_to' => $agent->id, 'updated_at' => now()]);

            if ($updated !== 1) {
                return $ticket->fresh();
            }

            $ticket->refresh();
            $ticket->activities()->create([
                'user_id' => $actor?->id,
                'event' => 'auto_assigned',
                'description' => "Automatically assigned to {$agent->name}.",
                'metadata' => ['strategy' => 'least_open_tickets'],
            ]);

            return $ticket;
        });
    }

    public function assign(Ticket $ticket, User $agent, User $actor): Ticket
    {
        abort_unless($agent->isAgent() && $agent->is_active, 422, 'The selected user is not an active support agent.');
        abort_unless($actor->isAdmin() || $actor->isAgent(), 403);

        return DB::transaction(function () use ($ticket, $agent, $actor) {
            $updated = Ticket::whereKey($ticket->id)
                ->whereNull('assigned_to')
                ->update(['assigned_to' => $agent->id, 'updated_at' => now()]);

            if ($updated !== 1) {
                throw new RuntimeException('This ticket has already been assigned.');
            }

            $ticket->refresh();
            $ticket->activities()->create([
                'user_id' => $actor->id,
                'event' => 'assigned',
                'description' => "Assigned to {$agent->name}.",
            ]);
            return $ticket;
        });
    }
}
