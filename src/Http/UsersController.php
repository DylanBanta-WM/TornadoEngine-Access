<?php

namespace TornadoEngine\Access\Http;

use App\Models\User;
use App\Support\GoogleSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use TornadoEngine\Access\Access;
use TornadoEngine\Access\Models\Exclusion;
use TornadoEngine\Access\Models\Role;

/**
 * The people who can sign in, and their roles. A person signs in with Google once they're added here.
 */
class UsersController extends Controller
{
    public function index(): View
    {
        return view('access::users.index', [
            // Excluded people are on the Excluded tab instead.
            'users' => User::with('roles')->whereNotIn('email', Exclusion::select('email'))->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
            'domain' => GoogleSignIn::domain(),
        ]);
    }

    public function store(Request $request, Access $access): RedirectResponse
    {
        $domain = GoogleSignIn::domain();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email'),
                function ($attribute, $value, $fail) use ($domain) {
                    if ($domain && ! str_ends_with(strtolower($value), '@'.$domain)) {
                        $fail(__('Use a :domain address. Only those can sign in.', ['domain' => $domain]));
                    }
                }],
            'name' => ['nullable', 'string', 'max:255'],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('access_roles', 'id')],
        ]);

        $email = strtolower($data['email']);
        $user = new User;
        $user->forceFill(['email' => $email, 'name' => ($data['name'] ?? null) ?: strstr($email, '@', true)])->save();
        $user->roles()->sync($data['roles'] ?? []);
        $access->forget();

        return redirect()->route('access.users.index')->with('status', __(':email can now sign in.', ['email' => $email]));
    }

    public function edit(User $user): View
    {
        return view('access::users.edit', ['user' => $user->load('roles'), 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user, Access $access): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('access_roles', 'id')],
        ]);

        $user->roles()->sync($data['roles'] ?? []);
        $access->forget();

        return redirect()->route('access.users.index')->with('status', __('Saved :name\'s roles.', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin || $user->is($request->user()), 403);

        $user->delete();

        return redirect()->route('access.users.index')->with('status', __(':email can no longer sign in.', ['email' => $user->email]));
    }
}
