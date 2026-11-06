<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Create Ticket</h2></x-slot>
    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="post" action="{{ route('tickets.store') }}" class="bg-white shadow rounded p-6 space-y-4">
            @csrf
            <div><x-input-label for="subject" value="Subject" /><x-text-input id="subject" name="subject" class="mt-1 block w-full" :value="old('subject')" required /><x-input-error :messages="$errors->get('subject')" /></div>
            <div><x-input-label for="description" value="Description" /><textarea id="description" name="description" class="mt-1 block w-full border-gray-300 rounded-md" rows="6" required>{{ old('description') }}</textarea><x-input-error :messages="$errors->get('description')" /></div>
            <div class="grid grid-cols-2 gap-4"><div><x-input-label for="priority" value="Priority" /><select id="priority" name="priority" class="mt-1 block w-full border-gray-300 rounded-md">@foreach(['low','medium','high','critical'] as $priority)<option value="{{ $priority }}">{{ ucfirst($priority) }}</option>@endforeach</select></div><div><x-input-label for="category" value="Category" /><x-text-input id="category" name="category" class="mt-1 block w-full" :value="old('category')" /></div></div>
            <x-primary-button>Create ticket</x-primary-button>
        </form>
    </div>
</x-app-layout>
