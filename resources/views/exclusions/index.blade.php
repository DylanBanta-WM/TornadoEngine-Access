<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    <x-settings-card :title="__('Exclude someone')">
        @include('access::exclusion-form')
    </x-settings-card>

    @if ($exclusions->isNotEmpty())
        <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            @foreach ($exclusions as $exclusion)
                <div class="px-6 py-4 space-y-2" x-data="{ editing: false }">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            @if ($exclusion->user)
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $exclusion->user->name }}</p>
                            @endif
                            <p class="break-all text-gray-600 dark:text-gray-400">{{ $exclusion->email }}</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ __($exclusion->reason) }} · {{ $exclusion->created_at->toFormattedDateString() }}@if ($exclusion->excludedBy) · {{ $exclusion->excludedBy->name }}@endif</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-secondary-button type="button" x-on:click="editing = ! editing">{{ __('Change reason') }}</x-secondary-button>
                            @if ($exclusion->user && ! $exclusion->user->is_admin)
                                <x-secondary-link :href="route('access.users.edit', $exclusion->user)">{{ __('Edit') }}</x-secondary-link>
                            @endif
                            <form method="POST" action="{{ route('access.exclusions.destroy', $exclusion) }}">
                                @csrf
                                @method('DELETE')
                                <x-secondary-button type="submit">{{ __('Un-exclude') }}</x-secondary-button>
                            </form>
                        </div>
                    </div>
                    <div x-show="editing" x-cloak class="max-w-md">
                        @include('access::exclusion-form', ['exclusion' => $exclusion])
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</x-admin-layout>
