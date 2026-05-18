<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('users.view'), 403);

        $sortable  = ['name', 'email'];
        $sort      = in_array($request->sort, $sortable) ? $request->sort : 'name';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $users = User::with('roleModel')->orderBy($sort, $direction)->paginate(25)->withQueryString();

        return view('users.index', compact('users', 'sort', 'direction'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('users.create'), 403);
        $roles = Role::orderBy('name')->get(['id', 'name', 'color']);
        return view('users.form', ['user' => null, 'roles' => $roles]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('users.create'), 403);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_id'  => 'nullable|exists:roles,id',
            'color'    => 'nullable|string|max:20',
            'initials' => 'nullable|string|max:5',
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('users.index')->with('success', 'Team member created.');
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.edit'), 403);
        $roles = Role::orderBy('name')->get(['id', 'name', 'color']);
        return view('users.form', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.edit'), 403);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role_id'  => 'nullable|exists:roles,id',
            'color'    => 'nullable|string|max:20',
            'initials' => 'nullable|string|max:5',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return redirect()->route('users.index')->with('success', 'Team member updated.');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->hasPermission('users.delete'), 403);
        abort_if($user->id === auth()->id(), 403, 'Cannot delete yourself.');
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Team member deleted.');
    }
}
