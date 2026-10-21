<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    use BulkDeletes;
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
        if ($reason = $this->deleteBlocker($role)) {
            return back()->with('error', $reason);
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    /**
     * Delete every selected row that passes the guard of the single delete.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->bulkIds($request, 'roles');

        $deleted = 0;
        $skipped = [];

        foreach (Role::whereIn('id', $ids)->get() as $role) {
            if ($reason = $this->deleteBlocker($role)) {
                $skipped[] = $reason;

                continue;
            }

            $role->delete();
            $deleted++;
        }

        return $this->bulkResult('admin.roles.index', $deleted, $skipped);
    }

    /**
     * Why the role cannot be deleted, or null when it can be deleted.
     */
    protected function deleteBlocker(Role $role): ?string
    {
        if ($role->name === 'Administrator') {
            return 'Role Administrator tidak bisa dihapus.';
        }

        if ($role->users()->exists()) {
            return 'Role masih digunakan oleh user dan tidak bisa dihapus.';
        }

        return null;
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
        // Make sure new permissions from the config already exist in the permissions table
        // before the Rule::exists validation, so it does not fail with "selected is invalid".
        $this->syncConfiguredPermissions();

        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->syncPermissions($request->input('permissions', []));

        return back()->with('success', "Permission untuk role {$role->name} berhasil diperbarui.");
    }

    /**
     * Sync the permissions from config/permissions.php into the permissions table
     * (only adds missing ones, never removes).
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
