<?php

namespace App\Modules\TicketManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TicketManagement\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return $this->render($request);
    }

    public function admin(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        return $this->render($request);
    }

    private function render(Request $request): View
    {
        $user = $request->user();
        $tickets = Ticket::query()->visibleTo($user);
        $open = (clone $tickets)->whereIn('status', ['open', 'in_progress', 'waiting_customer']);

        return view('modules.tickets.dashboard', [
            'user' => $user,
            'totalOpen' => (clone $open)->count(),
            'unassigned' => (clone $open)->whereNull('assigned_to')->count(),
            'critical' => (clone $open)->where('priority', 'critical')->count(),
            'breached' => (clone $open)->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'approaching' => (clone $open)->whereBetween('due_at', [now(), now()->addHours(2)])->count(),
            'resolved' => (clone $tickets)->whereIn('status', ['resolved', 'closed'])->count(),
            'byPriority' => (clone $tickets)->selectRaw('priority, count(*) as total')->groupBy('priority')->pluck('total', 'priority'),
            'myAssigned' => $user->isAgent() ? Ticket::where('assigned_to', $user->id)->whereIn('status', ['open', 'in_progress', 'waiting_customer'])->count() : 0,
            'customers' => $user->isAdmin() ? \App\Models\User::whereHas('roleDefinition', fn ($query) => $query->where('role_name', 'customer'))->count() : 0,
            'agents' => $user->isAdmin() ? \App\Models\User::whereHas('roleDefinition', fn ($query) => $query->where('role_name', 'agent'))->where('is_active', true)->count() : 0,
        ]);
    }
}
