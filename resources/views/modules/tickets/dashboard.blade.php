<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600">{{ $user->roleDefinition?->name ?? 'User' }} workspace</p>
                <h2 class="font-semibold text-2xl text-gray-900">
                    @if($user->isAdmin()) Admin Panel @elseif($user->isAgent()) Agent Workspace @else My Support Center @endif
                </h2>
            </div>
            <a href="{{ $user->isCustomer() ? route('tickets.create') : route('tickets.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                {{ $user->isCustomer() ? 'Create a ticket' : 'Open ticket' }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['label' => $user->isCustomer() ? 'My open tickets' : 'Open tickets', 'value' => $totalOpen, 'tone' => 'indigo'],
                    ['label' => $user->isAgent() ? 'Assigned to me' : 'Unassigned', 'value' => $user->isAgent() ? $myAssigned : $unassigned, 'tone' => 'amber'],
                    ['label' => 'Critical priority', 'value' => $critical, 'tone' => 'rose'],
                    ['label' => 'SLA breached', 'value' => $breached, 'tone' => 'red'],
                ] as $card)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            @if($user->isAdmin())
                <section>
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Administration</h3>
                            <p class="text-sm text-gray-500">Manage people and monitor support operations.</p>
                        </div>
                        <a href="{{ route('users.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Manage users →</a>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <a href="{{ route('users.index') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-indigo-300">
                            <p class="font-semibold text-gray-900">User directory</p>
                            <p class="mt-1 text-sm text-gray-500">{{ $agents }} active agents · {{ $customers }} customers</p>
                        </a>
                        <a href="{{ route('tickets.index') }}" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-indigo-300">
                            <p class="font-semibold text-gray-900">Operations queue</p>
                            <p class="mt-1 text-sm text-gray-500">Review assignment, SLA risk, and ticket activity.</p>
                        </a>
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

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Approaching SLA</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600">{{ $approaching }}</p>
                    <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="mt-3 inline-block text-sm font-semibold text-indigo-600">Review open tickets →</a>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-gray-500">Resolved or closed</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $resolved }}</p>
                    <a href="{{ route('tickets.index', ['status' => 'resolved']) }}" class="mt-3 inline-block text-sm font-semibold text-indigo-600">View resolved tickets →</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
