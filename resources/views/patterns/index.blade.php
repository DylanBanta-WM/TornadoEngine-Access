@php($select = 'mt-1 block w-full min-h-11 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-base focus:border-indigo-500 focus:ring-indigo-500')

<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    @if ($rules->isNotEmpty())
        <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg text-sm">
            <div class="hidden grid-cols-[minmax(0,1fr)_minmax(0,3fr)_8rem_6rem] gap-4 border-b border-gray-200 dark:border-gray-700 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 md:grid">
                <span>{{ __('Role') }}</span>
                <span>{{ __('Email pattern') }}</span>
                <span class="text-end">{{ __('People') }}</span>
                <span><span class="sr-only">{{ __('Remove') }}</span></span>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($rules as $rule)
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-2 px-6 py-4 md:grid-cols-[minmax(0,1fr)_minmax(0,3fr)_8rem_6rem] md:py-3">
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $rule->role->name }}</span>
                        <code class="col-span-2 row-start-2 rounded bg-gray-100 dark:bg-gray-900 px-2 py-1 font-mono text-[13px] leading-5 text-gray-800 dark:text-gray-200 break-all md:col-span-1 md:row-start-auto">{{ $rule->pattern }}</code>
                        <span class="col-start-2 row-start-1 text-end text-gray-500 dark:text-gray-400 md:col-start-auto md:row-start-auto">
                            @if ($rule->matched !== null)
                                {{ trans_choice(':count person|:count people', $rule->matched, ['count' => number_format($rule->matched)]) }}
                            @endif
                        </span>
                        <form method="POST" action="{{ route('access.patterns.destroy', $rule) }}" class="col-span-2 flex justify-end md:col-span-1">
                            @csrf
                            @method('DELETE')
                            <x-secondary-button type="submit">{{ __('Remove') }}</x-secondary-button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-settings-card :title="__('Add a rule')">
        <form method="POST" action="{{ route('access.patterns.store') }}" class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,3fr)_minmax(0,1fr)_auto] md:items-end">
            @csrf
            <div>
                <x-input-label for="pattern" :value="__('Email pattern')" />
                <x-text-input id="pattern" name="pattern" class="mt-1 block w-full font-mono text-[13px]" type="text" :value="old('pattern')" required autocapitalize="off" spellcheck="false" placeholder="^wm\d{2}[a-z]+@district\.org$" />
                <x-input-error :messages="$errors->get('pattern')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="role_id" :value="__('Role')" />
                <select id="role_id" name="role_id" class="{{ $select }}" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((int) old('role_id') === $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button class="justify-center md:mb-1">{{ __('Add rule') }}</x-primary-button>
        </form>
    </x-settings-card>
</x-admin-layout>
