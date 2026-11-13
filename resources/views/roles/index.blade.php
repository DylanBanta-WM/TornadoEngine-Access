<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    <div class="flex justify-end">
        <x-primary-link :href="route('access.roles.create')">{{ __('New role') }}</x-primary-link>
    </div>

    @if ($roles->isEmpty())
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 dark:text-gray-400">
            {{ __('No roles yet. A role is a set of permissions, such as "Tech staff", that you give to people.') }}
        </div>
    @else
        <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($roles as $role)
                <a href="{{ route('access.roles.edit', $role) }}" class="flex min-h-11 items-center justify-between gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <span class="min-w-0">
                        <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</span>
                        @if ($role->description)
                            <span class="block text-sm text-gray-600 dark:text-gray-400">{{ $role->description }}</span>
                        @endif
                    </span>
                    <span class="shrink-0 text-sm text-gray-500 dark:text-gray-400">{{ trans_choice(':count person|:count people', $role->users_count) }}</span>
                </a>
            @endforeach
        </section>
    @endif
</x-admin-layout>
