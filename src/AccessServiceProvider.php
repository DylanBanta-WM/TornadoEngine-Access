<?php

namespace TornadoEngine\Access;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use TornadoEngine\Access\Models\Role;
use TornadoEngine\Nav\Nav;

class AccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Access::class);
    }

    public function boot(Access $access): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'access');
        $this->loadRoutesFrom(__DIR__.'/../routes/access.php');

        User::resolveRelationUsing('roles', fn (User $user) => $user->belongsToMany(Role::class, 'access_role_user', 'user_id', 'role_id'));

        $access->permission('administration', __('Change the setup settings (Administration)'), 'Core');
        $access->permission('access.manage', __('Manage users and roles'), 'Access');

        // Access decides every permission it knows about; others are left to their own rules.
        Gate::before(fn ($user, string $ability) => $user instanceof User && $access->has($ability)
            ? $access->allows($user, $ability)
            : null);

        $this->callAfterResolving(Nav::class, function (Nav $nav) {
            $nav->user(__('Users and roles'), 'access.users.index', active: 'access.*', order: 800, can: 'access.manage');
            if (method_exists($nav, 'tool')) {
                $nav->tool(__('Users and roles'), 'access.users.index', __('Who can sign in, and what each role can open.'),
                    section: __('Administration'), icon: 'users', color: 'blue', can: 'access.manage');
            }
        });
    }
}
