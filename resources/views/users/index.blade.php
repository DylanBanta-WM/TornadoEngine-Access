@php
    $th = 'px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 first:ps-6 last:pe-6';
    $td = 'block md:table-cell md:px-4 md:py-3 md:align-middle md:first:ps-6 md:last:pe-6';
@endphp

<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    <x-settings-card :title="__('Add a person')">
        <form method="POST" action="{{ route('access.users.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="email" :value="__('School email')" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required :placeholder="$domain ? 'name@'.$domain : ''" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="name" :value="__('Name (optional)')" />
                    <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
            </div>
            @include('access::role-checkboxes', ['checked' => array_map('intval', old('roles', []))])
            @if (\TornadoEngine\Access\Http\UsersController::passwords())
                @include('access::password-fields', ['editing' => false])
            @endif
            <div class="flex justify-end">
                <x-primary-button>{{ __('Add') }}</x-primary-button>
            </div>
        </form>
    </x-settings-card>

    {{-- Search and filters; any change applies them. --}}
    <form method="GET" action="{{ route('access.users.index') }}" x-data x-on:change="$el.requestSubmit()"
          class="space-y-4 bg-white dark:bg-gray-800 p-4 shadow-sm sm:rounded-lg sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            <div>
                <x-input-label for="q" :value="__('Search')" />
                <x-text-input id="q" name="q" type="search" class="mt-1 block w-full" :value="$filters['q']" :placeholder="__('Name or email')" x-on:change.stop />
            </div>
            <div>
                <x-input-label for="sign_in" :value="__('Signs in with')" />
                <select id="sign_in" name="sign_in" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (['' => __('Any way'), 'google' => __('Google'), 'microsoft' => __('Microsoft'), 'password' => __('Website password'), 'none' => __('No way yet')] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['sign_in'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="per_page" :value="__('Per page')" />
                <select id="per_page" name="per_page" class="mt-1 block min-h-11 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (\TornadoEngine\Access\Http\UsersController::PER_PAGE as $size)
                        <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ number_format($size) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <fieldset>
            <legend class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Roles') }}</legend>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($roles->map(fn ($role) => [(string) $role->id, $role->name])->push(['none', __('No roles')]) as [$value, $label])
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-full border border-gray-300 dark:border-gray-600 px-4 text-sm text-gray-700 dark:text-gray-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-800 dark:has-[:checked]:bg-indigo-950/40 dark:has-[:checked]:text-indigo-200">
                        <input type="checkbox" name="roles[]" value="{{ $value }}" class="sr-only" @checked(in_array($value, $filters['roles'], true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $users->total() ? __('Showing :from–:to of :total', ['from' => number_format($users->firstItem()), 'to' => number_format($users->lastItem()), 'total' => number_format($users->total())]) : __('No one matches.') }}
            </p>
            <div class="flex items-center gap-3">
                @if ($filtered)
                    <a href="{{ route('access.users.index', ['per_page' => $filters['per_page']]) }}" class="inline-flex min-h-11 items-center text-sm font-medium text-gray-600 dark:text-gray-400 underline">{{ __('Clear filters') }}</a>
                @endif
                <x-primary-button>{{ __('Search') }}</x-primary-button>
            </div>
        </div>
    </form>

    <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
        <table class="block w-full text-sm md:table">
            <thead class="hidden border-b border-gray-200 dark:border-gray-700 md:table-header-group">
                <tr>
                    <th class="{{ $th }}">{{ __('Name') }}</th>
                    <th class="{{ $th }}">{{ __('Email') }}</th>
                    <th class="{{ $th }}">{{ __('Roles') }}</th>
                    <th class="{{ $th }}">{{ __('Signs in with') }}</th>
                    <th class="{{ $th }}"><span class="sr-only">{{ __('Actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="block divide-y divide-gray-200 dark:divide-gray-700 md:table-row-group">
                @foreach ($users as $user)
                    @php($signIn = array_keys(array_filter(['Google' => $user->google_id !== null, 'Microsoft' => ($user->microsoft_id ?? null) !== null, 'Website password' => $user->password !== null])))
                    <tr class="block space-y-1 px-6 py-4 md:table-row md:space-y-0 md:p-0">
                        <td class="{{ $td }} font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</td>
                        <td class="{{ $td }} break-all text-gray-600 dark:text-gray-400">{{ $user->email }}</td>
                        <td class="{{ $td }}">
                            <span class="flex flex-wrap gap-1">
                                @foreach ($user->roles as $role)
                                    <span class="rounded bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                                @endforeach
                                @if (! $user->is_admin && $user->roles->isEmpty())
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('No roles') }}</span>
                                @endif
                            </span>
                        </td>
                        <td class="{{ $td }} text-gray-600 dark:text-gray-400">{{ implode(', ', array_map(fn ($way) => __($way), $signIn)) }}</td>
                        <td class="{{ $td }} pt-2 md:pt-3">
                            @unless ($user->is_admin)
                                <div class="flex flex-wrap items-start gap-2 md:flex-nowrap md:justify-end">
                                    @foreach (app(\TornadoEngine\Access\Access::class)->userActions() as $action)
                                        @include($action, ['user' => $user])
                                    @endforeach
                                    <x-secondary-link :href="route('access.users.edit', $user)">{{ __('Edit') }}</x-secondary-link>
                                </div>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($users->hasPages())
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6">{{ $users->onEachSide(1)->links() }}</div>
        @endif
    </section>
</x-admin-layout>
