<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\CommissionType;
use App\Models\Department;
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
        $users = User::with('roles')->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'super-admin'))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('email', 'like', '%'.$request->search.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->status === 'active'))
            ->latest()->paginate(12)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return $this->form(new User);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $this->data($request);
            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar')->store('users', 'public');
            }
            $user = User::create($data);
            $user->roles()->sync($request->validated('roles'));
            $user->commissionTypes()->sync($request->validated('commission_type_ids', []));
        });

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        abort_if($user->hasRole('super-admin'), 404);

        return view('users.show', ['user' => $user->load(['roles', 'permissions', 'departmentMaster', 'manager'])]);
    }

    public function edit(User $user): View
    {
        abort_if($user->hasRole('super-admin'), 404);

        return $this->form($user);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('super-admin'), 404);

        DB::transaction(function () use ($request, $user) {
            $data = $this->data($request);
            if (! $request->filled('password')) {
                unset($data['password']);
            }
            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar')->store('users', 'public');
            }
            $user->update($data);
            $user->roles()->sync($request->validated('roles'));
            $user->commissionTypes()->sync($request->validated('commission_type_ids', []));
        });

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->hasRole('super-admin'), 404);
        abort_if($user->is(auth()->user()), 422, 'You cannot delete your own account.');
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User removed successfully.');
    }

    public function toggle(User $user): JsonResponse
    {
        $this->authorize('update', $user);
        abort_if($user->hasRole('super-admin'), 404);
        abort_if($user->is(auth()->user()), 422, 'You cannot deactivate your own account.');
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json(['message' => 'User status updated.', 'is_active' => $user->is_active]);
    }

    private function form(User $user): View
    {
        return view($user->exists ? 'users.edit' : 'users.create', [
            'user' => $user,
            'roles' => Role::where('is_active', true)->where('slug', '!=', 'super-admin')->get(),
            'departments' => Department::orderBy('department_name')->get(),
            'managers' => User::whereKeyNot($user->id)->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'super-admin'))->orderBy('name')->get(),
            'commissionTypes' => CommissionType::orderBy('type_name')->get(),
        ]);
    }

    private function data(UserRequest $request): array
    {
        $data = $request->safe()->except('roles', 'avatar', 'commission_type_ids');
        $data['is_active'] = $request->boolean('is_active');
        foreach (['monthly_salary', 'daily_salary', 'overtime_rate', 'travel_allowance', 'food_allowance', 'other_allowance', 'pf', 'esi', 'monthly_paid_leave_days'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        return $data;
    }
}
