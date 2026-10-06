<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index()
    {
        $this->syncConfiguredPermissions();

        $roles = Role::withCount('users')->orderBy('name')->get();

        return view('admin.role.index', [
            'roles' => $roles,
            'groups' => config('permissions', []),
            'permissions' => Permission::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
        ]);

        Role::create(['name' => $data['name']]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "Role {$data['name']} berhasil ditambahkan.");
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);

        $old = $role->name;
        $role->name = $data['name'];
        $role->save();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Administrator') {
            return back()->with('error', 'Role Administrator tidak bisa dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Role masih digunakan oleh user dan tidak bisa dihapus.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    public function permissions(Role $role)
    {
        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name'),
        ]);
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        // Pastikan permission baru di config sudah ada di tabel permissions
        // sebelum validasi Rule::exists, agar tidak gagal "selected is invalid".
        $this->syncConfiguredPermissions();

        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->syncPermissions($request->input('permissions', []));

        return back()->with('success', "Permission untuk role {$role->name} berhasil diperbarui.");
    }

    /**
     * Sinkronkan permission dari config/permissions.php ke tabel permissions
     * (hanya menambah yang belum ada, tidak pernah menghapus).
     */
    protected function syncConfiguredPermissions(): void
    {
        $configured = collect(config('permissions', []))
            ->flatMap(fn (array $group) => array_keys($group))
            ->values();

        $missing = $configured->diff(Permission::pluck('name'));

        if ($missing->isEmpty()) {
            return;
        }

        foreach ($missing as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
