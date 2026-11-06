<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-indigo-600">{{ now()->format('l, d M Y') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    @if($user->isAdmin()) Admin overview @elseif($user->isAgent()) Agent workspace @else My support center @endif
                </h2>
            </div>
            <a href="{{ $user->isCustomer() ? route('tickets.create') : route('tickets.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                {{ $user->isCustomer() ? '+ Create ticket' : 'View ticket queue' }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['label' => 'Total tickets', 'value' => $totalTickets, 'dot' => 'bg-indigo-500'],
                    ['label' => $user->isAgent() ? 'Assigned to me' : 'Open tickets', 'value' => $user->isAgent() ? $myAssigned : $totalOpen, 'dot' => 'bg-blue-500'],
                    ['label' => 'Unassigned', 'value' => $unassigned, 'dot' => 'bg-amber-500'],
                    ['label' => 'Critical tickets', 'value' => $critical, 'dot' => 'bg-rose-500'],
                ] as $card)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                            <span class="{{ $card['dot'] }} h-2.5 w-2.5 rounded-full"></span>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-slate-900">{{ number_format($card['value']) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Ticket operations</h3>
                            <p class="mt-1 text-sm text-slate-500">Monitor service health and SLA risk at a glance.</p>
                        </div>
                        <a href="{{ route('tickets.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Open queue →</a>
                    </div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-3">
                        <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="rounded-xl bg-amber-50 p-4 hover:bg-amber-100">
                            <p class="text-sm text-amber-700">SLA approaching</p>
                            <p class="mt-2 text-2xl font-bold text-amber-900">{{ $approaching }}</p>
                        </a>
                        <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="rounded-xl bg-red-50 p-4 hover:bg-red-100">
                            <p class="text-sm text-red-700">SLA breached</p>
                            <p class="mt-2 text-2xl font-bold text-red-900">{{ $breached }}</p>
                        </a>
                        <a href="{{ route('tickets.index', ['status' => 'resolved']) }}" class="rounded-xl bg-emerald-50 p-4 hover:bg-emerald-100">
                            <p class="text-sm text-emerald-700">Resolved tickets</p>
                            <p class="mt-2 text-2xl font-bold text-emerald-900">{{ $resolved }}</p>
                        </a>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">By priority</h3>
                            <p class="mt-1 text-sm text-slate-500">Current ticket distribution.</p>
                        </div>
                    </div>
                    <div class="mt-5 space-y-4">
                        @foreach(['critical' => 'bg-rose-500', 'high' => 'bg-orange-500', 'medium' => 'bg-blue-500', 'low' => 'bg-slate-400'] as $priority => $color)
                            <div>
                                <div class="mb-1 flex justify-between text-sm"><span class="capitalize text-slate-600">{{ $priority }}</span><span class="font-semibold text-slate-900">{{ $byPriority[$priority] ?? 0 }}</span></div>
                                <div class="h-2 rounded-full bg-slate-100"><div class="{{ $color }} h-2 rounded-full" style="width: {{ $totalTickets ? min(100, (($byPriority[$priority] ?? 0) / $totalTickets) * 100) : 0 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            @if($user->isAdmin())
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Agent workload</h3>
                            <p class="mt-1 text-sm text-slate-500">Active tickets assigned to each support agent.</p>
                        </div>
                        <a href="{{ route('users.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Manage users →</a>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @forelse($agentWorkloads as $agent)
                            <a href="{{ route('tickets.index', ['search' => $agent->name]) }}" class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 hover:border-indigo-200 hover:bg-indigo-50">
                                <span class="flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">{{ strtoupper(substr($agent->name, 0, 1)) }}</span><span class="font-medium text-slate-800">{{ $agent->name }}</span></span>
                                <span class="text-sm font-semibold text-slate-600">{{ $agent->open_ticket_count }} open</span>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No active support agents found.</p>
                        @endforelse
                    </div>
                </section>
            @elseif($user->isAgent())
                <section class="rounded-xl border border-indigo-100 bg-indigo-50 p-6">
                    <h3 class="text-lg font-semibold text-indigo-950">Your support queue</h3>
                    <p class="mt-1 text-sm text-indigo-800">Work assigned tickets, claim unassigned tickets, and keep customers updated.</p>
                    <a href="{{ route('tickets.index') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Open agent queue</a>
                </section>
            @else
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Need help?</h3>
                    <p class="mt-1 text-sm text-gray-500">Create a ticket and our support team will get back to you.</p>
                    <a href="{{ $user->isCustomer() ? route('tickets.create') : route('tickets.index') }}" class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Create support ticket</a>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
