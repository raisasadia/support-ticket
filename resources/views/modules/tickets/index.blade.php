<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Support Tickets</h2></x-slot>
    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('import_id'))
            <div x-data="ticketImport('{{ session('import_id') }}')" x-init="poll()" class="rounded border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">
                <div class="flex items-center justify-between">
                    <span x-text="message">CSV import queued.</span>
                    <span x-show="status === 'processing'" x-text="processed + ' / ' + total"></span>
                </div>
                <div x-show="status === 'processing'" class="mt-2 h-2 rounded bg-indigo-100">
                    <div class="h-2 rounded bg-indigo-600" :style="'width: ' + progress + '%'"></div>
                </div>
                <p x-show="status === 'completed'" class="mt-2 text-xs">
                    Imported: <span x-text="imported"></span> · Invalid: <span x-text="invalid"></span> · Duplicates: <span x-text="duplicates"></span>
                </p>
            </div>
        @endif
        <div class="flex items-start gap-3">
            <div id="ticket-filters" class="grid min-w-0 flex-1 grid-cols-1 gap-1.5 md:grid-cols-4 lg:grid-cols-8">
                <input name="search" value="{{ request('search') }}" placeholder="Ticket number or subject" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2">
                <select name="status" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    @foreach(['open','in_progress','waiting_customer','resolved','closed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                    @endforeach
                </select>
                <select name="priority" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All priorities</option>
                    @foreach(['low','medium','high','critical'] as $priority)
                        <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                <select name="category" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @if(!auth()->user()->isCustomer())
                    <select name="assigned_agent" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All agents</option>
                        <option value="unassigned" @selected(request('assigned_agent') === 'unassigned')>Unassigned</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected((string) request('assigned_agent') === (string) $agent->id)>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <select name="customer" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) request('customer') === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="sla_status" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All SLA statuses</option>
                    <option value="within" @selected(request('sla_status') === 'within')>Within SLA</option>
                    <option value="approaching" @selected(request('sla_status') === 'approaching')>Approaching</option>
                    <option value="breached" @selected(request('sla_status') === 'breached')>Breached</option>
                </select>
                <input type="date" name="created_from" value="{{ request('created_from') }}" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <input type="date" name="created_to" value="{{ request('created_to') }}" class="h-9 rounded-md border border-slate-300 px-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button type="button" id="apply-ticket-filters" class="h-9 rounded-md bg-slate-800 px-3 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                <button type="button" id="reset-ticket-filters" class="h-9 rounded-md bg-slate-100 px-3 text-sm font-medium text-slate-700 hover:bg-slate-200">Reset</button>
            </div>
            <a href="{{ route('tickets.create') }}" class="inline-flex shrink-0 items-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">+ New ticket</a>
        </div>
        @if(!auth()->user()->isCustomer())
            <form method="post" action="{{ route('tickets.import') }}" enctype="multipart/form-data" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required>
                <button class="rounded-md bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">Queue CSV import</button>
            </form>
        @endif
        <div class="bg-white shadow rounded overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3">Ticket</th>
                        <th class="p-3">Subject</th>
                        <th class="p-3">Priority</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Agent</th>
                        <th class="p-3">Created By</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="ticket-rows">
                @forelse($tickets as $ticket)
                    <tr class="ticket-row border-t"
                        data-search="{{ strtolower($ticket->ticket_number.' '.$ticket->subject) }}"
                        data-status="{{ $ticket->status }}"
                        data-priority="{{ $ticket->priority }}"
                        data-category="{{ strtolower($ticket->category ?? '') }}"
                        data-agent="{{ $ticket->assigned_to ?? '' }}"
                        data-customer="{{ $ticket->created_by }}"
                        data-created="{{ $ticket->created_at?->format('Y-m-d') }}"
                        data-due="{{ $ticket->due_at?->toIso8601String() ?? '' }}">
                        <td class="p-3">
                            <a class="text-indigo-600" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>
                        </td>
                        <td class="p-3">{{ $ticket->subject }}</td>
                        <td class="p-3">{{ ucfirst($ticket->priority) }}</td>
                        <td class="p-3">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</td>
                        <td class="p-3">{{ $ticket->agent?->name ?? 'Unassigned' }}</td>
                        <td class="p-3">{{ $ticket->creator?->name ?? 'Unknown' }}</td>
                        <td class="p-3"><a class="font-semibold text-indigo-600 hover:underline" href="{{ route('tickets.show', $ticket) }}">View</a>@if(auth()->user()->isAdmin() || $ticket->created_by === auth()->id() || (auth()->user()->isAgent() && $ticket->assigned_to === auth()->id()))<span class="mx-1 text-gray-300">|</span><a class="font-semibold text-indigo-600 hover:underline" href="{{ route('tickets.edit', $ticket) }}">Edit</a>@endif</td></tr>
                @empty
                    <tr id="no-tickets-row">
                        <td colspan="7" class="p-6 text-center text-gray-500">No tickets found.</td>
                    </tr>
                @endforelse
                    <tr id="no-filter-results-row" class="hidden">
                        <td colspan="7" class="p-6 text-center text-gray-500">No tickets match the selected filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p id="ticket-result-count" class="text-sm text-slate-500"></p>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filters = document.querySelectorAll('#ticket-filters [name]');
            const rows = [...document.querySelectorAll('.ticket-row')];
            const emptyRow = document.getElementById('no-filter-results-row');
            const count = document.getElementById('ticket-result-count');
            const value = name => document.querySelector(`#ticket-filters [name="${name}"]`)?.value || '';
            const matchesSla = (due, status) => {
                if (!status) return true;
                if (!due) return status === 'within';
                const deadline = new Date(due);
                const now = new Date();
                if (status === 'breached') return deadline < now;
                if (status === 'approaching') return deadline >= now && deadline <= new Date(now.getTime() + 2 * 60 * 60 * 1000);
                return deadline > new Date(now.getTime() + 2 * 60 * 60 * 1000);
            };
            const applyFilters = () => {
                const search = value('search').toLowerCase().trim();
                const visible = rows.filter(row => {
                    const created = row.dataset.created;
                    const from = value('created_from');
                    const to = value('created_to');
                    const match = (!search || row.dataset.search.includes(search))
                        && (!value('status') || row.dataset.status === value('status'))
                        && (!value('priority') || row.dataset.priority === value('priority'))
                        && (!value('category') || row.dataset.category === value('category').toLowerCase())
                        && (!value('assigned_agent') || (value('assigned_agent') === 'unassigned' ? !row.dataset.agent : row.dataset.agent === value('assigned_agent')))
                        && (!value('customer') || row.dataset.customer === value('customer'))
                        && (!from || created >= from)
                        && (!to || created <= to)
                        && matchesSla(row.dataset.due, value('sla_status'));
                    row.classList.toggle('hidden', !match);
                    return match;
                });
                emptyRow?.classList.toggle('hidden', visible.length > 0 || rows.length === 0);
                if (count) count.textContent = `Showing ${visible.length} of ${rows.length} tickets`;
            };
            filters.forEach(filter => filter.addEventListener('input', applyFilters));
            filters.forEach(filter => filter.addEventListener('change', applyFilters));
            document.getElementById('apply-ticket-filters')?.addEventListener('click', applyFilters);
            document.getElementById('reset-ticket-filters')?.addEventListener('click', () => {
                filters.forEach(filter => { filter.value = ''; });
                applyFilters();
            });
            applyFilters();
        });

        function ticketImport(id) {
            return {
                status: 'queued', total: 0, processed: 0, imported: 0, invalid: 0, duplicates: 0, progress: 0,
                message: 'CSV import queued.',
                poll() {
                    fetch('{{ url('/ticket-imports') }}/' + id + '/status')
                        .then(response => response.json())
                        .then(data => {
                            Object.assign(this, data);
                            this.progress = data.total ? Math.round((data.processed / data.total) * 100) : 0;
                            this.message = data.status === 'completed' ? 'CSV import completed.' : data.status === 'failed' ? 'CSV import failed.' : 'CSV import in progress.';
                            if (data.status !== 'completed' && data.status !== 'failed') setTimeout(() => this.poll(), 2000);
                        });
                }
            }
        }
    </script>
</x-app-layout>
