<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('employee')
            ->when(! $request->user()->isSuperAdmin(), fn ($query) => $query->where('role', '!=', User::ROLE_SUPER_ADMIN))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $search)->orWhere('email', 'like', $search));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        [$openModal, $editItem] = $this->modalState($request);

        return view('users.index', [
            'users' => $users,
            'filters' => $request->only('search', 'role'),
            'openModal' => $openModal,
            'editItem' => $editItem,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('users.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', ...$this->roleRule($request)],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLogger::record('created', 'User account created: '.$user->email, $user);

        return redirect()->route('users.index')->with('success', __('app.users.success_created'));
    }

    public function edit(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        return redirect()->route('users.index', ['open' => 'edit-'.$user->id]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', ...$this->roleRule($request)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isSelf = $user->id === $request->user()->id;

        if ($isSelf && (! in_array($data['role'], [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true) || ! $request->boolean('is_active'))) {
            throw ValidationException::withMessages([
                'role' => __('app.users.cannot_lock_self'),
            ]);
        }

        if ($user->isSuperAdmin() && $data['role'] !== User::ROLE_SUPER_ADMIN && $this->isLastSuperAdmin($user)) {
            throw ValidationException::withMessages([
                'role' => __('app.users.cannot_remove_last_super_admin'),
            ]);
        }

        $before = $user->getAttributes();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
            ...(filled($data['password'] ?? null) ? ['password' => $data['password']] : []),
        ]);

        ActivityLogger::record(
            'updated',
            'User account updated: '.$user->email,
            $user,
            ActivityLogger::changes($user, $before),
        );

        return redirect()->route('users.index')->with('success', __('app.users.success_updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($request, $user);

        if ($user->id === $request->user()->id) {
            return back()->with('error', __('app.users.cannot_delete_self'));
        }

        if ($user->isSuperAdmin() && $this->isLastSuperAdmin($user)) {
            return back()->with('error', __('app.users.cannot_remove_last_super_admin'));
        }

        ActivityLogger::record('deleted', 'User account deleted: '.$user->email, $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', __('app.users.success_deleted'));
    }

    /**
     * @return array<int, string>
     */
    private function roleRule(Request $request): array
    {
        return $request->user()->isSuperAdmin()
            ? ['in:admin,manager,employee,super_admin']
            : ['in:admin,manager,employee'];
    }

    private function authorizeUser(Request $request, User $user): void
    {
        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(404);
        }
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return User::where('role', User::ROLE_SUPER_ADMIN)->whereKeyNot($user->id)->doesntExist();
    }

    /**
     * @return array{0: ?string, 1: ?User}
     */
    private function modalState(Request $request): array
    {
        $open = $request->string('open')->toString();
        $editItem = null;

        if (preg_match('/^edit-(\d+)$/', $open, $matches)) {
            $editItem = User::find($matches[1]);

            if ($editItem?->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
                $editItem = null;
            }

            $open = $editItem ? 'user-edit-'.$editItem->id : null;
        } elseif ($open === 'create') {
            $open = 'user-create';
        }

        return [$open ?: null, $editItem];
    }
}
