<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                User Management
            </h2>

            <a href="{{ route('users.create') }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Create User
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error Messages --}}
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                {{-- Search and Filters --}}
                <form method="GET" action="{{ route('users.index') }}"
                      class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                            Search
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Name or email"
                            class="w-full border-gray-300 rounded-md shadow-sm"
                        >
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                            Role
                        </label>

                        <select name="role" id="role"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">All Roles</option>
                            <option value="admin" @selected(request('role') === 'admin')>
                                Administrator
                            </option>
                            <option value="agent" @selected(request('role') === 'agent')>
                                Support Agent
                            </option>
                            <option value="customer" @selected(request('role') === 'customer')>
                                Customer
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                            Account Status
                        </label>

                        <select name="status" id="status"
                                class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="1" @selected(request('status') === '1')>
                                Active
                            </option>
                            <option value="0" @selected(request('status') === '0')>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                            Filter
                        </button>

                        <a href="{{ route('users.index') }}"
                           class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                            Reset
                        </a>
                    </div>
                </form>

                {{-- User Table --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    ID
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Name
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Email
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Role
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Created At
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">

                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $user->id }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $user->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $user->email }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @switch($user->roleDefinition?->role_name)
                                            @case('admin')
                                                Administrator
                                                @break

                                            @case('agent')
                                                Support Agent
                                                @break

                                            @default
                                                Customer
                                        @endswitch
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @if ($user->is_active)
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $user->created_at?->format('d M Y') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-3">

                                            <a href="{{ route('users.edit', $user->id) }}"
                                               class="text-blue-600 hover:underline">
                                                Edit
                                            </a>

                                            @if ($user->id !== auth()->id())
                                                <form method="POST"
                                                      action="{{ route('users.toggle-status', $user->id) }}"
                                                      onsubmit="return confirm('Are you sure you want to change this user status?');">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit"
                                                            class="{{ $user->is_active ? 'text-red-600' : 'text-green-600' }} hover:underline">
                                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            @endif

                                        </div>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                        No users found.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>