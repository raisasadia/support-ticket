<?php
namespace App\Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Ensure only administrators can manage users.
     */
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    /**
     * Display users with search and filters.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roleDefinition', fn ($role) => $role->where('role_name', $request->role));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $users = $query->with('roleDefinition')->latest()
            ->paginate(15)
            ->withQueryString();

        return view('modules.users.index', compact('users'));
    }

    /**
     * Show user creation form.
     */
    public function create()
    {
        $this->authorizeAdmin();

        return view('modules.users.create', ['roles' => Role::orderBy('id')->get()]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role_id' => $validated['role_id'],
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show user editing form.
     */
    public function edit(User $user)
    {
        $this->authorizeAdmin();

        return view('modules.users.edit', [
            'user' => $user->load('roleDefinition'),
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    /**
     * Update user information.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Prevent an administrator from removing their own admin role.
        $selectedRole = Role::findOrFail($validated['role_id']);

        if ($user->id === auth()->id() && $selectedRole->role_name !== 'admin') {
            return back()->withErrors([
                'role_id' => 'You cannot remove your own administrator role.',
            ]);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_id = $validated['role_id'];

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Activate or deactivate an account.
     */
    public function toggleStatus(User $user)
    {
        $this->authorizeAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'status' => 'You cannot deactivate your own account.',
            ]);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return back()->with(
            'success',
            'User account status updated successfully.'
        );
    }
}
