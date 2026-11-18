<?php

namespace TornadoEngine\Access\Http;

use App\Models\User;
use App\Support\GoogleSignIn;
use App\Support\SignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use TornadoEngine\Access\Access;
use TornadoEngine\Access\Models\Exclusion;
use TornadoEngine\Access\Models\Role;
use TornadoEngine\Access\Patterns;

/**
 * The people who can sign in, and their roles. A person signs in with Google once they're added here.
 */
class UsersController extends Controller
{
    /** Results per page the list offers. */
    public const PER_PAGE = [10, 25, 50, 100, 250, 500, 1000, 2500];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'max:20'],
            'sign_in' => ['nullable', Rule::in(['google', 'microsoft', 'password', 'none'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE)],
        ]);
        // The page size is remembered for next time.
        $perPage = (int) ($filters['per_page'] ?? session('access.users.per_page', 25));
        session(['access.users.per_page' => $perPage]);

        $query = User::with('roles')->whereNotIn('email', Exclusion::select('email')); // Excluded people are on the Excluded tab instead.
        if (($search = trim($filters['q'] ?? '')) !== '') {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }
        $roles = $filters['roles'] ?? [];
        if ($roles !== []) {
            $ids = array_map('intval', array_filter($roles, 'ctype_digit'));
            $query->where(function ($q) use ($ids, $roles) {
                if ($ids !== []) {
                    $q->orWhereHas('roles', fn ($r) => $r->whereIn((new Role)->getTable().'.id', $ids));
                }
                if (in_array('none', $roles, true)) {
                    $q->orWhere(fn ($n) => $n->doesntHave('roles')->where('is_admin', false));
                }
            });
        }
        match ($filters['sign_in'] ?? null) {
            'google' => $query->whereNotNull('google_id'),
            'microsoft' => $query->whereNotNull('microsoft_id'),
            'password' => $query->whereNotNull('password'),
            'none' => $query->whereNull('google_id')->whereNull('microsoft_id')->whereNull('password'),
            default => null,
        };

        return view('access::users.index', [
            'users' => $query->orderBy('name')->orderBy('id')->paginate($perPage)->withQueryString(),
            'roles' => Role::orderBy('name')->get(),
            'domain' => GoogleSignIn::domain(),
            'filters' => ['q' => $search, 'roles' => $roles, 'sign_in' => $filters['sign_in'] ?? '', 'per_page' => $perPage],
            'filtered' => $search !== '' || $roles !== [] || ($filters['sign_in'] ?? null),
        ]);
    }

    /** Whether people can have website passwords (Core's "Website passwords" sign-in). */
    public static function passwords(): bool
    {
        return class_exists(SignIn::class) && SignIn::enabled('password');
    }

    public function store(Request $request, Access $access): RedirectResponse
    {
        // With Google as the only way in, only that domain's accounts can sign in.
        $domain = class_exists(SignIn::class) && SignIn::methods() !== ['google'] ? null : GoogleSignIn::domain();

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
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'must_change_password' => ['boolean'],
        ]);

        $email = strtolower($data['email']);
        $user = new User;
        $user->forceFill(['email' => $email, 'name' => ($data['name'] ?? null) ?: strstr($email, '@', true)]);
        if (self::passwords()) {
            $user->forceFill([
                'password' => Hash::make(($data['password'] ?? null) ?: SignIn::DEFAULT_PASSWORD),
                'must_change_password' => $request->boolean('must_change_password', true),
            ]);
        }
        $user->save();
        $user->roles()->sync($data['roles'] ?? []);
        $access->forget();
        app(Patterns::class)->apply();

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
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'must_change_password' => ['boolean'],
        ]);

        $user->roles()->sync($data['roles'] ?? []);
        if (self::passwords() && $request->has('must_change_password')) {
            if ($data['password'] ?? null) {
                $user->password = Hash::make($data['password']);
            }
            $user->must_change_password = $user->password !== null && $request->boolean('must_change_password');
            $user->save();
        }
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
