<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">{{ $ticket->ticket_number }} · {{ $ticket->subject }}</h2></x-slot>
    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('success'))<div class="bg-green-100 p-3 rounded">{{ session('success') }}</div>@endif
        <div class="bg-white shadow rounded p-6 space-y-2"><p class="text-gray-700 whitespace-pre-line">{{ $ticket->description }}</p><p><strong>Priority:</strong> {{ ucfirst($ticket->priority) }} · <strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($ticket->status)) }}</p><p><strong>SLA due:</strong> {{ $ticket->due_at?->format('Y-m-d H:i') ?? 'Not set' }} · <strong>Agent:</strong> {{ $ticket->agent?->name ?? 'Unassigned' }}</p></div>
        <div class="bg-white shadow rounded p-6"><h3 class="font-semibold mb-4">Conversation</h3>@forelse($ticket->replies as $reply)<div class="border-t py-3"><p class="text-sm text-gray-500">{{ $reply->user->name }} · {{ $reply->created_at->format('Y-m-d H:i') }} @if($reply->is_internal)<span class="text-orange-600">(Internal note)</span>@endif</p><p class="whitespace-pre-line">{{ $reply->body }}</p></div>@empty<p class="text-gray-500">No replies yet.</p>@endforelse<form method="post" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data" class="mt-4">@csrf<textarea name="body" class="w-full border-gray-300 rounded" rows="4" placeholder="Write a reply..." required></textarea><input type="file" name="attachments[]" multiple class="mt-2">@if(!auth()->user()->isCustomer())<label class="block mt-2"><input type="checkbox" name="is_internal" value="1"> Internal note</label>@endif<x-primary-button class="mt-2">Send</x-primary-button></form></div>
        <div class="bg-white shadow rounded p-6"><h3 class="font-semibold mb-3">Attachments</h3>@forelse($ticket->attachments as $attachment)<a class="block text-indigo-600" href="{{ route('tickets.attachments.download', $attachment) }}">{{ $attachment->original_name }}</a>@empty<p class="text-gray-500">No attachments.</p>@endforelse<form method="post" action="{{ route('tickets.attachments.store', $ticket) }}" enctype="multipart/form-data" class="mt-4 flex gap-3">@csrf<input type="file" name="file" required><button class="bg-gray-800 text-white rounded px-3 py-2">Upload</button></form></div>
        @if(auth()->user()->isAgent() && !$ticket->assigned_to)<div class="bg-white shadow rounded p-6"><h3 class="font-semibold mb-3">Available ticket</h3><p class="mb-3 text-sm text-gray-500">Claim this ticket to add it to your queue.</p><form method="post" action="{{ route('tickets.claim', $ticket) }}">@csrf @method('patch')<x-primary-button>Claim ticket</x-primary-button></form></div>@endif
        @if(auth()->user()->isAdmin())
            <div class="bg-white shadow rounded p-6">
                <h3 class="font-semibold mb-3">Assignment</h3>
                @if($agents->isEmpty())
                    <p class="text-sm text-amber-700">No active support agents are available. Create or activate an agent account in Users first.</p>
                @else
                    <form method="post" action="{{ route('tickets.assign', $ticket) }}" class="flex flex-wrap gap-2">
                        @csrf
                        @method('patch')
                        <select name="agent_id" class="rounded border-gray-300" required>
                            <option value="">Select an agent</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        <x-primary-button>Assign</x-primary-button>
                    </form>
                    @if(!$ticket->assigned_to)
                        <form method="post" action="{{ route('tickets.auto-assign', $ticket) }}" class="mt-3">
                            @csrf
                            @method('patch')
                            <button class="rounded bg-indigo-100 px-3 py-2 text-sm font-semibold text-indigo-800 hover:bg-indigo-200">Auto-assign by workload</button>
                        </form>
                    @endif
                @endif
            </div>
        @endif
        @if(!auth()->user()->isCustomer())<div class="bg-white shadow rounded p-6"><form method="post" action="{{ route('tickets.status', $ticket) }}" class="flex gap-2">@csrf @method('patch')<select name="status" class="border rounded">@foreach(['open','in_progress','waiting_customer','resolved','closed'] as $status)<option value="{{ $status }}" @selected($ticket->status === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach</select><x-primary-button>Update status</x-primary-button></form></div>@endif
    </div>
</x-app-layout>
