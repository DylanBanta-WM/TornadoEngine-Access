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
    </section>
</x-admin-layout>
