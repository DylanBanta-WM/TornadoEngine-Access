@php($select = 'mt-1 block w-full min-h-11 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-base focus:border-indigo-500 focus:ring-indigo-500')

<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    <x-settings-card :title="__('Roles from email patterns')">
        @if ($rules->isNotEmpty())
            <ul class="divide-y divide-gray-200 dark:divide-gray-700 rounded-md border border-gray-200 dark:border-gray-700">
                @foreach ($rules as $rule)
                    <li class="flex flex-col gap-1 px-3 py-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $rule->role->name }}</p>
                            <p class="break-all"><code class="font-mono">{{ $rule->pattern }}</code></p>
                            @if ($rule->matched !== null)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ trans_choice(':count person|:count people', $rule->matched) }}</p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('access.patterns.destroy', $rule) }}" class="-ms-2 sm:ms-0">
                            @csrf
                            @method('DELETE')
                            <button class="min-h-11 px-2 text-sm text-red-700 dark:text-red-400 underline">{{ __('Remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('access.patterns.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <x-input-label for="role_id" :value="__('Role')" />
                    <select id="role_id" name="role_id" class="{{ $select }}" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id') === $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="pattern" :value="__('Email pattern (regular expression)')" />
                    <x-text-input id="pattern" name="pattern" class="mt-1 block w-full font-mono" type="text" :value="old('pattern')" required autocapitalize="off" spellcheck="false" placeholder="^wm\d{2}[a-z]+@district\.org$" />
                    <x-input-error :messages="$errors->get('pattern')" class="mt-2" />
                </div>
            </div>
            <div class="flex justify-end">
                <x-primary-button>{{ __('Add rule') }}</x-primary-button>
            </div>
        </form>
    </x-settings-card>
</x-admin-layout>
