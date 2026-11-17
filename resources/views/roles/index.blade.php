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
        <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
            <div class="hidden grid-cols-[minmax(0,1fr)_minmax(0,2fr)_8rem] gap-4 border-b border-gray-200 dark:border-gray-700 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 md:grid">
                <span>{{ __('Role') }}</span>
                <span>{{ __('Description') }}</span>
                <span class="text-end">{{ __('People') }}</span>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($roles as $role)
                    <a href="{{ route('access.roles.edit', $role) }}" class="grid min-h-11 grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1 px-6 py-4 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 md:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_8rem] md:py-3">
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</span>
                        <span class="col-span-2 row-start-2 text-gray-600 dark:text-gray-400 md:col-span-1 md:row-start-auto empty:hidden md:empty:block">{{ $role->description }}</span>
                        <span class="col-start-2 row-start-1 text-end text-gray-500 dark:text-gray-400 md:col-start-auto md:row-start-auto">{{ trans_choice(':count person|:count people', $role->users_count) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-admin-layout>
