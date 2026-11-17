@php($select = 'mt-1 block w-full min-h-11 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-base focus:border-indigo-500 focus:ring-indigo-500')

<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    @if ($rules->isNotEmpty())
        <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700">
            @foreach ($rules as $rule)
                <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-baseline gap-x-3">
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $rule->role->name }}</span>
                            @if ($rule->matched !== null)
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ trans_choice(':count person|:count people', $rule->matched, ['count' => number_format($rule->matched)]) }}</span>
                            @endif
                        </div>
                        <code class="block rounded bg-gray-100 dark:bg-gray-900 px-2 py-1 font-mono text-[13px] leading-5 text-gray-800 dark:text-gray-200 break-all">{{ $rule->pattern }}</code>
                    </div>
                    <form method="POST" action="{{ route('access.patterns.destroy', $rule) }}" class="shrink-0 self-end sm:self-auto">
                        @csrf
                        @method('DELETE')
                        <x-secondary-button type="submit">{{ __('Remove') }}</x-secondary-button>
                    </form>
                </div>
            @endforeach
        </section>
    @endif

    <x-settings-card :title="__('Add a rule')">
        <form method="POST" action="{{ route('access.patterns.store') }}" class="space-y-4">
            @csrf
            <div>
                <x-input-label for="pattern" :value="__('Email pattern')" />
                <x-text-input id="pattern" name="pattern" class="mt-1 block w-full font-mono text-[13px]" type="text" :value="old('pattern')" required autocapitalize="off" spellcheck="false" placeholder="^wm\d{2}[a-z]+@district\.org$" />
                <x-input-error :messages="$errors->get('pattern')" class="mt-2" />
            </div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="sm:w-64">
                    <x-input-label for="role_id" :value="__('Role')" />
                    <select id="role_id" name="role_id" class="{{ $select }}" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id') === $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button class="justify-center sm:ms-auto">{{ __('Add rule') }}</x-primary-button>
            </div>
        </form>
    </x-settings-card>
</x-admin-layout>
