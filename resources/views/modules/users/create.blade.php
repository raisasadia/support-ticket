
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Create User
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">

                <form method="POST" action="{{ route('users.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block mb-2">Full Name</label>
                        <input type="text" name="name"
                               value="{{ old('name') }}"
                               class="w-full border-gray-300 rounded-md"
                               required>
                        @error('name')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Email</label>
                        <input type="email" name="email"
                               value="{{ old('email') }}"
                               class="w-full border-gray-300 rounded-md"
                               required>
                        @error('email')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Role</label>
                        <select name="role_id" class="w-full border-gray-300 rounded-md" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id', $roles->firstWhere('role_name', 'customer')?->id) == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Password</label>
                        <input type="password" name="password"
                               class="w-full border-gray-300 rounded-md"
                               required>
                        @error('password')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block mb-2">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border-gray-300 rounded-md"
                               required>
                    </div>

                    <button type="submit"
                            class="px-5 py-2 bg-gray-800 text-white rounded-md">
                        Create User
                    </button>

                    <a href="{{ route('users.index') }}"
                       class="ml-2 px-5 py-2 bg-gray-200 rounded-md">
                        Cancel
                    </a>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
