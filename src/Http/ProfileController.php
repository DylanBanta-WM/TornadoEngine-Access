<?php

namespace TornadoEngine\Access\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use TornadoEngine\Access\Access;
use TornadoEngine\AccessMicrosoftAd\Connection;
use TornadoEngine\Nav\Nav;

/**
 * Someone's own page: how they sign in, their roles (and where each came from), and what they can open.
 */
class ProfileController extends Controller
{
    /** What each import source is called. */
    public const SOURCES = ['google' => 'Google', 'microsoft-ad' => 'Active Directory', 'microsoft-entra' => 'Entra ID', 'email-pattern' => 'Email pattern'];

    public function __invoke(Request $request, Access $access): View
    {
        $user = $request->user();

        $signIn = array_keys(array_filter([
            'Google' => $user->google_id !== null,
            'Microsoft' => ($user->microsoft_id ?? null) !== null,
            'Website password' => $user->password !== null,
            'Active Directory' => class_exists(Connection::class)
                && Connection::signInEnabled()
                && DB::table('access_imported_roles')->where('user_id', $user->id)->where('source', 'microsoft-ad')->exists(),
        ]));

        $sources = DB::table('access_imported_roles')->where('user_id', $user->id)->get()->groupBy('role_id');
        $roles = $user->roles()->orderBy('name')->get()->map(fn ($role) => [
            'name' => $role->name,
            'from' => $sources->has($role->id)
                ? $sources[$role->id]->pluck('source')->map(fn ($source) => self::SOURCES[$source] ?? $source)->all()
                : [__('Given by hand')],
        ]);

        $permissions = [];
        foreach ($access->grouped() as $module => $list) {
            $allowed = array_filter($list, fn ($permission) => $user->can($permission->key));
            if ($allowed) {
                $permissions[$module] = $allowed;
            }
        }

        return view('access::profile', [
            'user' => $user,
            'signIn' => $signIn,
            'roles' => $roles,
            'permissions' => $permissions,
            'tools' => class_exists(Nav::class) && method_exists(Nav::class, 'toolsFor')
                ? app(Nav::class)->toolsFor($user) : [],
        ]);
    }
}
