<?php

namespace App\Modules\TicketManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\TicketManagement\Models\Ticket;
use App\Modules\TicketManagement\Services\SlaDeadlineCalculator;
use App\Modules\TicketManagement\Services\TicketAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Modules\TicketManagement\Jobs\ImportTicketsJob;
use App\Modules\TicketManagement\Models\TicketAttachment;
use App\Modules\TicketManagement\Notifications\TicketEventNotification;
use Illuminate\View\View;
use RuntimeException;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tickets = Ticket::query()
            ->visibleTo($user)
            ->with(['creator:id,name', 'agent:id,name'])
            ->latest()
            ->get();

        $agents = User::query()
            ->whereHas('roleDefinition', fn ($query) => $query->where('role_name', 'agent'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $customers = $user->isCustomer()
            ? collect()
            : User::query()
                ->whereHas('roleDefinition', fn ($query) => $query->where('role_name', 'customer'))
                ->orderBy('name')
                ->get(['id', 'name']);
        $categories = $tickets->pluck('category')->filter()->unique()->sort()->values();

        return view('modules.tickets.index', compact('tickets', 'agents', 'customers', 'categories'));
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless(!$request->user()->isCustomer(), 403);
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $path = $request->file('file')->store('ticket-imports');
        $importId = (string) str()->uuid();
        Cache::put('ticket-import:'.$importId, [
            'status' => 'queued',
            'total' => 0,
            'processed' => 0,
            'imported' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'errors' => [],
        ], now()->addDay());
        ImportTicketsJob::dispatch($path, $request->user()->id, $importId);

        return back()
            ->with('success', 'The CSV import was queued for processing.')
            ->with('import_id', $importId);
    }

    public function importStatus(Request $request, string $import): \Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()->isAdmin() || !$request->user()->isCustomer(), 403);

        return response()->json(Cache::get('ticket-import:'.$import, [
            'status' => 'not_found',
        ]));
    }

    public function create(): View
    {
        return view('modules.tickets.create');
    }

    public function store(Request $request, SlaDeadlineCalculator $calculator, TicketAssignmentService $assignment): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $ticket = DB::transaction(function () use ($data, $request, $calculator, $assignment) {
            $ticket = Ticket::create([
                ...$data,
                'ticket_number' => 'TMP-'.str()->random(20),
                'created_by' => $request->user()->id,
                'due_at' => $calculator->calculate($data['priority'], now()),
            ]);
            $ticket->update(['ticket_number' => 'TKT-'.$ticket->created_at->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT)]);
            $ticket->activities()->create([
                'user_id' => $request->user()->id,
                'event' => 'created',
                'description' => 'Ticket created.',
            ]);
            $assignment->autoAssign($ticket, $request->user());
            return $ticket;
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket created successfully.');
    }

    public function edit(Request $request, Ticket $ticket): View
    {
        $this->authorizeTicketEdit($request, $ticket);

        return view('modules.tickets.edit', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicketEdit($request, $ticket);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $changes = [];
        foreach (['subject', 'description', 'priority', 'category'] as $field) {
            if ($ticket->{$field} !== ($data[$field] ?? null)) {
                $changes[$field] = ['from' => $ticket->{$field}, 'to' => $data[$field] ?? null];
            }
        }

        $ticket->update($data);

        if ($changes !== []) {
            $ticket->activities()->create([
                'user_id' => $request->user()->id,
                'event' => 'updated',
                'description' => 'Ticket details updated.',
                'metadata' => $changes,
            ]);
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket updated successfully.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        abort_unless($ticket->created_by === $request->user()->id || !$request->user()->isCustomer(), 403);
        $ticket->load(['creator:id,name,email', 'agent:id,name', 'replies.user:id,name,role_id', 'activities.user:id,name', 'attachments.reply']);
        if ($request->user()->isCustomer()) {
            $ticket->setRelation('replies', $ticket->replies->where('is_internal', false)->values());
            $ticket->setRelation('activities', $ticket->activities->where('event', '!=', 'internal_note')->values());
            $ticket->setRelation('attachments', $ticket->attachments->filter(fn ($attachment) => $attachment->reply_id === null || !$attachment->reply?->is_internal)->values());
        }
        $agents = $request->user()->isCustomer()
            ? collect()
            : User::query()->whereHas('roleDefinition', fn ($query) => $query->where('role_name', 'agent'))->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('modules.tickets.show', compact('ticket', 'agents'));
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->created_by === $request->user()->id || !$request->user()->isCustomer(), 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,csv,zip'],
        ]);
        $internal = (bool) ($data['is_internal'] ?? false);
        abort_if($internal && $request->user()->isCustomer(), 403);
        $reply = $ticket->replies()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'is_internal' => $internal]);
        $ticket->activities()->create(['user_id' => $request->user()->id, 'event' => $internal ? 'internal_note' : 'reply', 'description' => $internal ? 'Internal note added.' : 'Reply added.']);
        if (!$internal) {
            $recipient = $request->user()->isCustomer() ? $ticket->agent : $ticket->creator;
            $recipient?->notify(new TicketEventNotification($ticket, $request->user()->isCustomer() ? 'Customer replied' : 'Agent replied'));
        }
        $this->storeAttachments($request, $ticket, $reply->id);
        return back()->with('success', 'Reply added.');
    }

    public function assign(Request $request, Ticket $ticket, TicketAssignmentService $assignment): RedirectResponse
    {
        $data = $request->validate(['agent_id' => ['required', 'integer', 'exists:users,id']]);
        try {
            $assignment->assign($ticket, User::findOrFail($data['agent_id']), $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['agent_id' => $exception->getMessage()]);
        }
        $ticket->agent?->notify(new TicketEventNotification($ticket, 'Ticket assigned'));
        return back()->with('success', 'Ticket assigned.');
    }

    public function autoAssign(Request $request, Ticket $ticket, TicketAssignmentService $assignment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($ticket->assigned_to) {
            return back()->withErrors(['assignment' => 'This ticket is already assigned.']);
        }

        $assigned = $assignment->autoAssign($ticket, $request->user());

        return $assigned?->assigned_to
            ? back()->with('success', 'Ticket automatically assigned using the least-workload strategy.')
            : back()->withErrors(['assignment' => 'No active support agents are available.']);
    }

    public function claim(Request $request, Ticket $ticket, TicketAssignmentService $assignment): RedirectResponse
    {
        abort_unless($request->user()->isAgent(), 403);

        try {
            $assignment->assign($ticket, $request->user(), $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['assignment' => $exception->getMessage()]);
        }

        $ticket->activities()->create([
            'user_id' => $request->user()->id,
            'event' => 'claimed',
            'description' => 'Ticket claimed by '.$request->user()->name.'.',
        ]);

        return back()->with('success', 'Ticket claimed successfully.');
    }

    public function status(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless(!$request->user()->isCustomer(), 403);
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,waiting_customer,resolved,closed']]);
        $previousStatus = $ticket->status;
        $ticket->update([
            'status' => $data['status'],
            'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? now() : null,
            'closed_at' => $data['status'] === 'closed' ? now() : null,
        ]);
        if ($previousStatus !== $data['status']) {
            $ticket->activities()->create([
                'user_id' => $request->user()->id,
                'event' => 'status_changed',
                'description' => 'Status changed from '.str_replace('_', ' ', ucfirst($previousStatus)).' to '.str_replace('_', ' ', ucfirst($data['status'])).'.',
                'metadata' => ['from' => $previousStatus, 'to' => $data['status']],
            ]);
        }
        if (in_array($data['status'], ['resolved', 'closed'], true)) {
            $ticket->creator?->notify(new TicketEventNotification($ticket, 'Ticket resolved'));
        }
        return back()->with('success', 'Ticket status updated.');
    }

    public function attachment(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->created_by === $request->user()->id || !$request->user()->isCustomer(), 403);
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,txt,csv,zip'],
        ]);
        $this->storeAttachments($request, $ticket);
        return back()->with('success', 'Attachment uploaded.');
    }

    public function download(Request $request, TicketAttachment $attachment): StreamedResponse
    {
        $ticket = $attachment->ticket;
        abort_unless($ticket->created_by === $request->user()->id || !$request->user()->isCustomer(), 403);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function storeAttachments(Request $request, Ticket $ticket, ?int $replyId = null): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }
        foreach ($request->file('attachments') as $file) {
            $path = $file->store('ticket-attachments');
            $ticket->attachments()->create([
                'reply_id' => $replyId,
                'uploaded_by' => $request->user()->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        }
    }

    private function authorizeTicketEdit(Request $request, Ticket $ticket): void
    {
        $user = $request->user();
        $allowed = $user->isAdmin()
            || $ticket->created_by === $user->id
            || ($user->isAgent() && $ticket->assigned_to === $user->id);

        abort_unless($allowed, 403);
    }
}
