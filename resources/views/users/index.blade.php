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
            <p class="text-gray-600 dark:text-gray-400">{{ __('They can sign in with their Google account once they\'re added. Their name is filled in from Google when they do.') }}</p>
            <div class="flex justify-end">
                <x-primary-button>{{ __('Add') }}</x-primary-button>
            </div>
        </form>
    </x-settings-card>

    <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
        @foreach ($users as $user)
            <div class="flex flex-col gap-2 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 break-all">{{ $user->email }}</p>
                    <p class="mt-1 flex flex-wrap gap-1">
                        @if ($user->is_admin)
                            <span class="rounded bg-indigo-50 dark:bg-indigo-900/50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300">{{ __('Setup admin: everything') }}</span>
                        @endif
                        @foreach ($user->roles as $role)
                            <span class="rounded bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                        @endforeach
                        @if (! $user->is_admin && $user->roles->isEmpty())
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('No roles') }}</span>
                        @endif
                    </p>
                </div>
                @unless ($user->is_admin)
                    <div class="flex flex-wrap items-start gap-2 self-start sm:justify-end sm:self-auto">
                        @foreach (app(\TornadoEngine\Access\Access::class)->userActions() as $action)
                            @include($action, ['user' => $user])
                        @endforeach
                        <x-secondary-link :href="route('access.users.edit', $user)">{{ __('Edit') }}</x-secondary-link>
                    </div>
                @endunless
            </div>
        @endforeach
    </section>
</x-admin-layout>
