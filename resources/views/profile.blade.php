<x-admin-layout :title="__('Profile')">
    @if (session('status'))
        <p class="rounded-md bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-800 dark:text-green-300">{{ session('status') }}</p>
    @endif

    <x-settings-card :title="$user->name">
        <p class="break-all">{{ $user->email }}</p>
        @if ($signIn)
            <p><span class="font-medium text-gray-900 dark:text-gray-100">{{ __('Signs in with:') }}</span> {{ implode(', ', array_map(fn ($way) => __($way), $signIn)) }}</p>
        @endif
        @if ($user->password !== null && Route::has('password.edit'))
            <div class="flex justify-end">
                <x-secondary-link :href="route('password.edit')">{{ __('Change password') }}</x-secondary-link>
            </div>
        @endif
    </x-settings-card>

    <x-settings-card :title="__('Roles')">
        @if ($roles->isNotEmpty())
            <ul class="divide-y divide-gray-200 dark:divide-gray-700 rounded-md border border-gray-200 dark:border-gray-700">
                @foreach ($roles as $role)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2">
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $role['name'] }}</span>
                        <span class="flex flex-wrap gap-1">
                            @foreach ($role['from'] as $from)
                                <span class="rounded bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">{{ $from }}</span>
                            @endforeach
                        </span>
                    </li>
                @endforeach
            </ul>
        @elseif (! $user->is_admin)
            <p>{{ __('No roles yet.') }}</p>
        @endif
    </x-settings-card>

    <x-settings-card :title="__('What you can open')">
        @if ($tools)
            <div class="space-y-4">
                @foreach ($tools as $section => $list)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $section }}</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($list as $tool)
                                <li><a href="{{ route($tool->route) }}" class="inline-flex min-h-11 items-center font-medium text-indigo-600 dark:text-indigo-400 underline">{{ $tool->label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($permissions)
            <details>
                <summary class="flex min-h-11 cursor-pointer items-center font-medium text-gray-900 dark:text-gray-100">{{ __('Permissions') }}</summary>
                <div class="space-y-3">
                    @foreach ($permissions as $module => $list)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $module }}</p>
                            <ul class="mt-1 list-disc ps-5">
                                @foreach ($list as $permission)
                                    <li>{{ $permission->label }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </details>
        @elseif (! $tools)
            <p>{{ __('Nothing yet. Ask an administrator for access.') }}</p>
        @endif
    </x-settings-card>
</x-admin-layout>
