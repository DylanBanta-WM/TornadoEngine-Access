{{-- People each role's primary rule missed, from the last sync. $missed from PrimaryRules::missed(). --}}
@foreach ($missed ?? [] as $section)
    <div>
        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ __('People the primary rule for :role missed', ['role' => $section['role']]) }} ({{ count($section['people']) }})</h4>
        <p class="text-gray-600 dark:text-gray-400">{{ __('The primary rule is :rule.', ['rule' => $section['primary']]) }}</p>
        @if ($section['people'])
            <ul class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($section['people'] as $person)
                    <li class="min-w-0 rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2">
                        @if ($person['name'])
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $person['name'] }}</p>
                        @endif
                        <p class="break-all text-gray-600 dark:text-gray-400">{{ $person['email'] }}</p>
                        <p class="break-all text-gray-600 dark:text-gray-400">{{ $person['detail'] ? $person['detail'].' ' : '' }}{{ __('Caught by :rules.', ['rules' => implode(__(' and '), $person['caught_by'])]) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endforeach
