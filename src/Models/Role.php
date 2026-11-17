<?php

namespace TornadoEngine\Access\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    protected $table = 'access_roles';

    protected $fillable = ['name', 'description', 'all_permissions'];

    protected function casts(): array
    {
        return ['all_permissions' => 'boolean'];
    }

    /** The Admin role: the first with every permission (the setup admin has it). It keeps every permission and can't be removed. */
    public static function admin(): ?self
    {
        return self::where('all_permissions', true)->orderBy('id')->first();
    }

    public function isAdmin(): bool
    {
        return $this->exists && $this->id === self::admin()?->id;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'access_role_user');
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return DB::table('access_role_permissions')->where('role_id', $this->id)->orderBy('permission')->pluck('permission')->all();
    }

    /** @param  list<string>  $permissions */
    public function setPermissions(array $permissions): void
    {
        DB::transaction(function () use ($permissions) {
            DB::table('access_role_permissions')->where('role_id', $this->id)->delete();
            DB::table('access_role_permissions')->insert(
                array_map(fn ($permission) => ['role_id' => $this->id, 'permission' => $permission], array_values(array_unique($permissions))),
            );
        });
    }
}
