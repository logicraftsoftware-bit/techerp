<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): View
    {
        $users = User::with('roles')->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('email', 'like', '%'.$request->search.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest()->paginate(12)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User, 'roles' => Role::where('is_active', true)->get()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->except('roles') + ['is_active' => $request->boolean('is_active')]);
            $user->roles()->sync($request->validated('roles'));
        });

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        return view('users.show', ['user' => $user->load('roles.permissions')]);
    }

    public function edit(User $user): View
    {
        return view('users.edit', ['user' => $user, 'roles' => Role::where('is_active', true)->get()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $data = $request->safe()->except('roles', 'password');
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }
            $data['is_active'] = $request->boolean('is_active');
            $user->update($data);
            $user->roles()->sync($request->validated('roles'));
        });

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'You cannot delete your own account.');
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User removed successfully.');
    }

    public function toggle(User $user): JsonResponse
    {
        $this->authorize('update', $user);
        abort_if($user->is(auth()->user()), 422, 'You cannot deactivate your own account.');
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['message' => 'User status updated.', 'is_active' => $user->is_active]);
    }
}
