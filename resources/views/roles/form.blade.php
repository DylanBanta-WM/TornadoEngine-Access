<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    <x-settings-card :title="$role->exists ? $role->name : __('New role')">
        <form method="POST" action="{{ $role->exists ? route('access.roles.update', $role) : route('access.roles.store') }}" class="space-y-6" x-data="{ all: @js((bool) old('all_permissions', $role->all_permissions)) }">
            @csrf
            @if ($role->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name', $role->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div class="lg:col-span-2">
                    <x-input-label for="description" :value="__('Description (optional)')" />
                    <x-text-input id="description" class="mt-1 block w-full" type="text" name="description" :value="old('description', $role->description)" />
                </div>
            </div>

            <label class="flex min-h-11 items-center gap-3 rounded-md border border-gray-200 dark:border-gray-700 px-3 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-950/40 md:w-fit md:pe-5">
                <input type="hidden" name="all_permissions" value="0">
                <input type="checkbox" name="all_permissions" value="1" x-model="all" @disabled($role->isAdmin()) class="rounded text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500 disabled:opacity-60">
                <span class="text-gray-900 dark:text-gray-100">{{ __('Every permission, including ones modules add later') }}</span>
            </label>

            <fieldset x-show="! all">
                <legend class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('What people with this role can do') }}</legend>
                <div class="mt-3 grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                    @php($checked = old('permissions', $granted))
                    @foreach ($groups as $module => $permissions)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $module }}</p>
                            <div class="mt-2 space-y-2">
                                @foreach ($permissions as $permission)
                                    <label class="flex min-h-11 items-center gap-3 rounded-md border border-gray-200 dark:border-gray-700 px-3 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-950/40">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->key }}" class="rounded text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500" @checked(in_array($permission->key, $checked, true))>
                                        <span class="text-gray-900 dark:text-gray-100">{{ $permission->label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('permissions.*')" class="mt-2" />
            </fieldset>

            <div class="flex justify-end gap-3">
                <x-secondary-link :href="route('access.roles.index')">{{ __('Cancel') }}</x-secondary-link>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        @if ($role->exists && ! $role->isAdmin())
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <x-confirm :action="route('access.roles.destroy', $role)" method="DELETE" :message="__('Remove the :name role? People with it lose its permissions.', ['name' => $role->name])" :confirm="__('Remove role')" danger>
                    <x-slot name="trigger"><x-danger-button type="button">{{ __('Remove role') }}</x-danger-button></x-slot>
                </x-confirm>
            </div>
        @endif
    </x-settings-card>
</x-admin-layout>
