@php
    $th = 'px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 first:ps-6 last:pe-6';
    $td = 'block md:table-cell md:px-4 md:py-3 md:align-middle md:first:ps-6 md:last:pe-6';
@endphp

<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    <x-settings-card :title="__('Exclude someone')">
        @include('access::exclusion-form', ['inline' => true])
    </x-settings-card>

    @if ($exclusions->isNotEmpty())
        <section class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
            <table class="block w-full text-sm md:table">
                <thead class="hidden border-b border-gray-200 dark:border-gray-700 md:table-header-group">
                    <tr>
                        <th class="{{ $th }}">{{ __('Name') }}</th>
                        <th class="{{ $th }}">{{ __('Email') }}</th>
                        <th class="{{ $th }}">{{ __('Reason') }}</th>
                        <th class="{{ $th }}"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                @foreach ($exclusions as $exclusion)
                    <tbody class="block border-gray-200 dark:border-gray-700 md:table-row-group [&:not(:first-of-type)]:border-t" x-data="{ editing: false }">
                        <tr class="block space-y-1 px-6 pt-4 md:table-row md:space-y-0 md:p-0" x-bind:class="editing ? '' : 'pb-4'">
                            <td class="{{ $td }} font-medium text-gray-900 dark:text-gray-100 empty:hidden md:empty:table-cell">@if ($exclusion->user){{ $exclusion->user->name }}@endif</td>
                            <td class="{{ $td }} break-all text-gray-600 dark:text-gray-400">{{ $exclusion->email }}</td>
                            <td class="{{ $td }} text-gray-600 dark:text-gray-400">{{ __($exclusion->reason) }} · {{ $exclusion->created_at->toFormattedDateString() }}@if ($exclusion->excludedBy) · {{ $exclusion->excludedBy->name }}@endif</td>
                            <td class="{{ $td }} pt-2 md:pt-3">
                                <div class="flex flex-wrap gap-2 md:flex-nowrap md:justify-end">
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
                            </td>
                        </tr>
                        <tr x-show="editing" x-cloak class="block px-6 pb-4 pt-3 md:table-row md:p-0">
                            <td colspan="4" class="block md:table-cell md:px-6 md:pb-4">
                                <div class="md:max-w-3xl">
                                    @include('access::exclusion-form', ['exclusion' => $exclusion, 'inline' => true])
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforeach
            </table>
        </section>
    @endif
</x-admin-layout>
