<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('roles.view'), 403);
        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('roles.create'), 403);
        $permissions = Permission::orderBy('slug')->get();
        return view('roles.form', ['role' => null, 'permissions' => $permissions]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('roles.create'), 403);

        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255|unique:roles,slug',
            'color'          => 'nullable|string|max:20',
            'description'    => 'nullable|string',
            'permissions'    => 'nullable|array',
            'permissions.*'  => 'exists:permissions,id',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name'], '-');
        $role = Role::create(\Arr::except($data, 'permissions'));
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(Role $role)
    {
        abort_unless(auth()->user()->hasPermission('roles.edit'), 403);
        $permissions = Permission::orderBy('slug')->get();
        $role->load('permissions');
        return view('roles.form', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        abort_unless(auth()->user()->hasPermission('roles.edit'), 403);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'color'         => 'nullable|string|max:20',
            'description'   => 'nullable|string',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(\Arr::except($data, 'permissions'));
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        abort_unless(auth()->user()->hasPermission('roles.delete'), 403);
        abort_if($role->isSuperAdmin(), 403, 'Cannot delete super-admin role.');
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }
}
