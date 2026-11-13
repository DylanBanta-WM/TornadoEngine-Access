<?php

namespace TornadoEngine\Access;

use App\Models\User;
use TornadoEngine\Access\Models\Role;

/**
 * The permissions modules use, and who has them. Modules declare theirs in their service provider:
 *
 *     $this->callAfterResolving(Access::class, function (Access $access) {
 *         $access->permission('notes.view', 'See notes', module: 'Notes');
 *     });
 *
 * Pages check them the usual Laravel way: the "can:notes.view" middleware, @can, or $user->can().
 * Admins give permissions to roles and roles to users. The account made during first-time
 * setup always has every permission.
 */
class Access
{
    /** @var array<string, Permission> */
    private array $permissions = [];

    /** @var array<int, list<string>> Each user's permissions, loaded once per request. */
    private array $granted = [];

    public function permission(string $key, string $label, string $module): static
    {
        $this->permissions[$key] = new Permission($key, $label, $module);

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->permissions[$key]);
    }

    /**
     * The declared permissions, grouped by module.
     *
     * @return array<string, list<Permission>>
     */
    public function grouped(): array
    {
        $groups = [];
        foreach ($this->permissions as $permission) {
            $groups[$permission->module][] = $permission;
        }
        ksort($groups);

        return $groups;
    }

    public function allows(User $user, string $key): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $this->granted[$user->id] ??= Role::query()
            ->join('access_role_user', 'access_role_user.role_id', '=', 'access_roles.id')
            ->join('access_role_permissions', 'access_role_permissions.role_id', '=', 'access_roles.id')
            ->where('access_role_user.user_id', $user->id)
            ->distinct()
            ->pluck('access_role_permissions.permission')
            ->all();

        return in_array($key, $this->granted[$user->id], true);
    }

    /**
     * Forget loaded permissions, after roles change.
     */
    public function forget(): void
    {
        $this->granted = [];
    }
}
