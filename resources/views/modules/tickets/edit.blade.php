<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Edit {{ $ticket->ticket_number }}</h2>
            <a href="{{ route('tickets.show', $ticket) }}" class="text-sm font-semibold text-indigo-600 hover:underline">Back to ticket</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="post" action="{{ route('tickets.update', $ticket) }}" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="subject" value="Subject" />
                    <x-text-input id="subject" name="subject" class="mt-1 block w-full" :value="old('subject', $ticket->subject)" required />
                    <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="7" class="mt-1 block w-full rounded-md border-gray-300" required>{{ old('description', $ticket->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="priority" value="Priority" />
                        <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300">
                            @foreach(['low','medium','high','critical'] as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', $ticket->priority) === $priority)>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="category" value="Category" />
                        <x-text-input id="category" name="category" class="mt-1 block w-full" :value="old('category', $ticket->category)" />
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="rounded bg-gray-900 px-4 py-2 font-semibold text-white hover:bg-gray-700">Save changes</button>
                    <a href="{{ route('tickets.show', $ticket) }}" class="rounded bg-gray-100 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-200">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
