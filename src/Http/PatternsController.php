<?php

namespace TornadoEngine\Access\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use TornadoEngine\Access\Models\PatternRule;
use TornadoEngine\Access\Models\Role;
use TornadoEngine\Access\Patterns;

class PatternsController extends Controller
{
    public function index(): View
    {
        return view('access::patterns.index', [
            'rules' => PatternRule::with('role')->get()->sortBy(fn (PatternRule $rule) => [$rule->role->name, $rule->pattern]),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Patterns $patterns): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('access_roles', 'id')],
            'pattern' => ['required', 'string', 'max:500', function ($attribute, $value, $fail) {
                if (! PatternRule::valid($value)) {
                    $fail(__('That isn\'t a valid regular expression.'));
                }
            }],
        ], [], ['role_id' => __('role')]);

        PatternRule::firstOrCreate(['role_id' => $data['role_id'], 'pattern' => trim($data['pattern'])]);
        $patterns->apply();

        return back()->with('status', __('Added.'));
    }

    public function destroy(PatternRule $rule, Patterns $patterns): RedirectResponse
    {
        $rule->delete();
        $patterns->apply();

        return back()->with('status', __('Removed.'));
    }
}
