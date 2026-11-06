<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Support Tickets</h2></x-slot>
    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="flex justify-between">
            <form class="flex gap-2">
                <input name="search" value="{{ request('search') }}" placeholder="Ticket number or subject" class="border rounded px-3 py-2">
                <select name="status" class="border rounded px-3 py-2">
                    <option value="">All statuses</option>
                    @foreach(['open','in_progress','waiting_customer','resolved','closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
                <button class="bg-gray-800 text-white rounded px-4">Filter</button>
            </form>
            <a href="{{ route('tickets.create') }}" class="inline-flex items-center rounded bg-gray-900 px-4 py-2 font-semibold text-white shadow hover:bg-gray-700">+ New ticket</a>
        </div>
        @if(!auth()->user()->isCustomer())
            <form method="post" action="{{ route('tickets.import') }}" enctype="multipart/form-data" class="bg-white shadow rounded p-4 flex gap-3 items-center">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required>
                <button class="bg-gray-800 text-white rounded px-4 py-2">Queue CSV import</button>
            </form>
        @endif
        <div class="bg-white shadow rounded overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50"><tr><th class="p-3">Ticket</th><th class="p-3">Subject</th><th class="p-3">Priority</th><th class="p-3">Status</th><th class="p-3">Agent</th><th class="p-3">Actions</th></tr></thead>
                <tbody>
                @forelse($tickets as $ticket)
                    <tr class="border-t"><td class="p-3"><a class="text-indigo-600" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td><td class="p-3">{{ $ticket->subject }}</td><td class="p-3">{{ ucfirst($ticket->priority) }}</td><td class="p-3">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</td><td class="p-3">{{ $ticket->agent?->name ?? 'Unassigned' }}</td><td class="p-3"><a class="font-semibold text-indigo-600 hover:underline" href="{{ route('tickets.show', $ticket) }}">View</a>@if(auth()->user()->isAdmin() || $ticket->created_by === auth()->id() || (auth()->user()->isAgent() && $ticket->assigned_to === auth()->id()))<span class="mx-1 text-gray-300">|</span><a class="font-semibold text-indigo-600 hover:underline" href="{{ route('tickets.edit', $ticket) }}">Edit</a>@endif</td></tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-gray-500">No tickets found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $tickets->links() }}
    </div>
</x-app-layout>
