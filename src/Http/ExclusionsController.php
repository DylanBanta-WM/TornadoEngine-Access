<?php

namespace TornadoEngine\Access\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use TornadoEngine\Access\Imports;
use TornadoEngine\Access\Models\Exclusion;

/**
 * People every import (Access-Google, Access-AD, …) leaves out.
 */
class ExclusionsController extends Controller
{
    public function index(): View
    {
        return view('access::exclusions.index', [
            'exclusions' => Exclusion::with(['excludedBy', 'user'])->orderBy('email')->get(),
        ]);
    }

    public function store(Request $request, Imports $imports): RedirectResponse
    {
        $data = $this->validated($request, withEmail: true);
        $imports->exclude($data['email'], $data['reason'], $request->user());

        return back()->with('status', __(':email is excluded.', ['email' => strtolower($data['email'])]));
    }

    public function update(Request $request, Exclusion $exclusion): RedirectResponse
    {
        $exclusion->update(['reason' => $this->validated($request)['reason']]);

        return back()->with('status', __('Saved.'));
    }

    public function destroy(Exclusion $exclusion): RedirectResponse
    {
        $exclusion->delete();

        return back()->with('status', __(':email isn\'t excluded anymore. Imports include them again at their next sync.', ['email' => $exclusion->email]));
    }

    /** @return array{email?: string, reason: string} */
    private function validated(Request $request, bool $withEmail = false): array
    {
        $data = $request->validate(array_filter([
            'email' => $withEmail ? ['required', 'email', 'max:255'] : null,
            'reason' => ['required', 'string', 'max:255'],
            'other' => ['nullable', 'required_if:reason,other', 'string', 'max:255'],
        ]), [], ['other' => __('reason')]);

        $data['reason'] = $data['reason'] === 'other' ? trim($data['other']) : $data['reason'];

        return $data;
    }
}
