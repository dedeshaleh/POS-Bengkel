<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureMenuPermission;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccessControlController extends Controller
{
    public function users()
    {
        return view('master.users', [
            'users' => User::with('roles')->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function createUser()
    {
        return view('master.users-create', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_ids'][0],
        ]);

        $user->roles()->sync($data['role_ids']);
        EnsureMenuPermission::flushPermissionCache();

        return back()->with('status', 'User and roles saved.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'min:6'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'exists:roles,id'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_ids'][0],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $user->roles()->sync($data['role_ids']);
        EnsureMenuPermission::flushPermissionCache();

        return redirect()->route('master.users')->with('status', 'User updated.');
    }

    public function showUser(User $user)
    {
        $user->load('roles');
        return view('master.users-show', compact('user'));
    }

    public function editUser(User $user)
    {
        $user->load('roles');
        return view('master.users-edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function confirmDeactivateUser(User $user)
    {
        $user->load('roles');
        return view('master.users-delete', compact('user'));
    }

    public function deactivateUser(User $user)
    {
        $user->update(['is_active' => false]);

        return back()->with('status', 'User set to NonAktif.');
    }

    public function activateUser(User $user)
    {
        $user->update(['is_active' => true]);

        return back()->with('status', 'User activated.');
    }

    public function menus()
    {
        return view('master.menus', [
            'menus' => Menu::with('parent')->orderBy('sort_order')->paginate(10)->withQueryString(),
            'roleAccesses' => RolePermission::with(['role', 'menu'])->latest('id')->paginate(10)->withQueryString(),
        ]);
    }

    public function createMenu()
    {
        return view('master.menus-create', [
            'parentMenus' => Menu::orderBy('sort_order')->get(),
        ]);
    }

    public function storeMenu(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'],
            'url' => ['required', 'max:255', 'unique:menus,url'],
            'icon' => ['nullable', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'is_progress' => ['nullable', 'boolean'],
        ]);

        $data['is_progress'] = $request->boolean('is_progress');

        Menu::create($data);
        EnsureMenuPermission::flushPermissionCache();

        return redirect()->route('master.menus')->with('status', 'Menu saved.');
    }

    public function showMenu(Menu $menu)
    {
        $menu->load('parent');
        return view('master.menus-show', compact('menu'));
    }

    public function editMenu(Menu $menu)
    {
        return view('master.menus-edit', [
            'menu' => $menu,
            'parentMenus' => Menu::where('id', '!=', $menu->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function updateMenu(Request $request, Menu $menu)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'],
            'url' => ['required', 'max:255', 'unique:menus,url,' . $menu->id],
            'icon' => ['nullable', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'exists:menus,id'],
            'is_progress' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['parent_id']) && (int) $data['parent_id'] === (int) $menu->id) {
            return back()->withErrors(['parent_id' => 'Parent menu cannot be itself.']);
        }

        $data['is_progress'] = $request->boolean('is_progress');
        $menu->update($data);
        EnsureMenuPermission::flushPermissionCache();

        return redirect()->route('master.menus')->with('status', 'Menu updated.');
    }

    public function confirmDeleteMenu(Menu $menu)
    {
        $menu->load('parent');
        return view('master.menus-delete', compact('menu'));
    }

    public function destroyMenu(Menu $menu)
    {
        $menu->delete();
        EnsureMenuPermission::flushPermissionCache();
        return redirect()->route('master.menus')->with('status', 'Menu deleted.');
    }

    public function createRoleAccess()
    {
        return view('master.menu-access-create', [
            'roles' => Role::orderBy('name')->get(),
            'menus' => Menu::orderBy('sort_order')->get(),
        ]);
    }

    public function saveRoleMenuAccess(Request $request)
    {
        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'menu_id' => ['required', 'exists:menus,id'],
            'can_read' => ['nullable', 'boolean'],
            'can_create' => ['nullable', 'boolean'],
            'can_update' => ['nullable', 'boolean'],
            'can_delete' => ['nullable', 'boolean'],
        ]);

        RolePermission::updateOrCreate(
            [
                'role_id' => $data['role_id'],
                'menu_id' => $data['menu_id'],
            ],
            [
                'can_read' => $request->boolean('can_read'),
                'can_create' => $request->boolean('can_create'),
                'can_update' => $request->boolean('can_update'),
                'can_delete' => $request->boolean('can_delete'),
            ]
        );
        EnsureMenuPermission::flushPermissionCache();

        return redirect()->route('master.menus')->with('status', 'Role access updated.');
    }

    public function editRoleAccess(RolePermission $access)
    {
        return view('master.menu-access-edit', [
            'access' => $access->load(['role', 'menu']),
            'roles' => Role::orderBy('name')->get(),
            'menus' => Menu::orderBy('sort_order')->get(),
        ]);
    }

    public function updateRoleAccess(Request $request, RolePermission $access)
    {
        $data = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'menu_id' => ['required', 'exists:menus,id'],
            'can_read' => ['nullable', 'boolean'],
            'can_create' => ['nullable', 'boolean'],
            'can_update' => ['nullable', 'boolean'],
            'can_delete' => ['nullable', 'boolean'],
        ]);

        $exists = RolePermission::where('role_id', $data['role_id'])
            ->where('menu_id', $data['menu_id'])
            ->where('id', '!=', $access->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['menu_id' => 'Access for this role and menu already exists.']);
        }

        $access->update([
            'role_id' => $data['role_id'],
            'menu_id' => $data['menu_id'],
            'can_read' => $request->boolean('can_read'),
            'can_create' => $request->boolean('can_create'),
            'can_update' => $request->boolean('can_update'),
            'can_delete' => $request->boolean('can_delete'),
        ]);
        EnsureMenuPermission::flushPermissionCache();

        return redirect()->route('master.menus')->with('status', 'Role access updated.');
    }

    public function confirmDeleteRoleAccess(RolePermission $access)
    {
        return view('master.menu-access-delete', [
            'access' => $access->load(['role', 'menu']),
        ]);
    }

    public function destroyRoleAccess(RolePermission $access)
    {
        $access->delete();
        EnsureMenuPermission::flushPermissionCache();
        return redirect()->route('master.menus')->with('status', 'Role access deleted.');
    }

    public function roles()
    {
        return view('master.roles', [
            'roles' => Role::withCount('users')->orderBy('name')->paginate(10)->withQueryString(),
        ]);
    }

    public function storeRole(Request $request)
    {
        Role::create($request->validate([
            'name' => ['required', 'max:50', 'unique:roles,name'],
        ]));

        return back()->with('status', 'Role saved.');
    }
}
