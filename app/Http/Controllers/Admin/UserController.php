<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $roleFilter = (string) $request->query('role');

        $query = User::query()->with('roles')->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter !== '') {
            $query->whereHas('roles', fn($builder) => $builder->where('name', $roleFilter));
        }

        $users = $query->paginate(10)->withQueryString();

        return view('admin.user.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
            'search' => $search,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user)
    {
        $user->load('roles');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'is_active' => (bool) $user->is_active,
            'verified_at' => optional($user->email_verified_at)->format('d M Y, H:i'),
            'created_at' => optional($user->created_at)->format('d M Y, H:i'),
            'updated_at' => optional($user->updated_at)->format('d M Y, H:i'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($user->id === auth()->id() && ! $request->boolean('is_active')) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun yang sedang login.');
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_active = $request->boolean('is_active');

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles(array_filter([$data['role'] ?? null]));

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun yang sedang login.');
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
