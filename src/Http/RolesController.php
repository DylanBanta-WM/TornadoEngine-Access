<?php

namespace TornadoEngine\Access\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use TornadoEngine\Access\Access;
use TornadoEngine\Access\Models\Role;

/**
 * Roles: named sets of permissions, given to users.
 */
class RolesController extends Controller
{
    public function index(): View
    {
        return view('access::roles.index', ['roles' => Role::withCount('users')->orderBy('name')->get()]);
    }

    public function create(Access $access): View
    {
        return view('access::roles.form', ['role' => new Role, 'granted' => [], 'groups' => $access->grouped()]);
    }

    public function store(Request $request, Access $access): RedirectResponse
    {
        $data = $this->validated($request, $access);

        $role = Role::create(['name' => $data['name'], 'description' => $data['description'] ?? null, 'all_permissions' => $request->boolean('all_permissions')]);
        $role->setPermissions($data['permissions'] ?? []);
        $access->forget();

        return redirect()->route('access.roles.index')->with('status', __('Added the :name role.', ['name' => $role->name]));
    }

    public function edit(Role $role, Access $access): View
    {
        return view('access::roles.form', ['role' => $role, 'granted' => $role->permissions(), 'groups' => $access->grouped()]);
    }

    public function update(Request $request, Role $role, Access $access): RedirectResponse
    {
        $data = $this->validated($request, $access, $role);

        $role->update(['name' => $data['name'], 'description' => $data['description'] ?? null, 'all_permissions' => $role->isAdmin() || $request->boolean('all_permissions')]);
        $role->setPermissions($data['permissions'] ?? []);
        $access->forget();

        return redirect()->route('access.roles.index')->with('status', __('Saved the :name role.', ['name' => $role->name]));
    }

    public function destroy(Role $role, Access $access): RedirectResponse
    {
        abort_if($role->isAdmin(), 403);

        $role->delete();
        $access->forget();

        return redirect()->route('access.roles.index')->with('status', __('Removed the :name role.', ['name' => $role->name]));
    }

    /** @return array{name: string, description?: string|null, permissions?: list<string>} */
    private function validated(Request $request, Access $access, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('access_roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', function ($attribute, $value, $fail) use ($access) {
                if (! $access->has($value)) {
                    $fail(__('Unknown permission.'));
                }
            }],
        ]);
    }
}
