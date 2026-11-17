{{-- Exclude someone, or change why ($exclusion). $email is set when it's for a known person. $inline: fields in a row on wide screens. --}}
@php
    $reasons = \TornadoEngine\Access\Models\Exclusion::REASONS;
    $current = isset($exclusion) ? (in_array($exclusion->reason, $reasons, true) ? $exclusion->reason : 'other') : $reasons[0];
    $id = isset($exclusion) ? 'x'.$exclusion->id : ($email ?? 'new');
@endphp
<form method="POST" action="{{ isset($exclusion) ? route('access.exclusions.update', $exclusion) : route('access.exclusions.store') }}" @class(['space-y-3' => empty($inline), 'space-y-3 md:flex md:items-end md:gap-3 md:space-y-0 md:[&>div]:flex-1' => ! empty($inline)]) x-data="{ reason: @js($current) }">
    @csrf
    @isset($exclusion)
        @method('PUT')
    @endisset
    @if (isset($email))
        <input type="hidden" name="email" value="{{ $email }}">
    @elseif (! isset($exclusion))
        <div>
            <x-input-label :for="'exclude-email-'.$id" :value="__('Email')" />
            <x-text-input :id="'exclude-email-'.$id" name="email" type="email" class="mt-1 block w-full" required autocapitalize="off" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
    @endif
    <div>
        <x-input-label :for="'exclude-reason-'.$id" :value="__('Reason')" />
        <select id="exclude-reason-{{ $id }}" name="reason" x-model="reason" class="mt-1 block w-full min-h-11 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-base focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($reasons as $reason)
                <option value="{{ $reason }}">{{ __($reason) }}</option>
            @endforeach
            <option value="other">{{ __('Other') }}</option>
        </select>
    </div>
    <div x-show="reason === 'other'" x-cloak>
        <x-input-label :for="'exclude-other-'.$id" :value="__('Other reason')" />
        <x-text-input :id="'exclude-other-'.$id" name="other" type="text" class="mt-1 block w-full" x-bind:required="reason === 'other'" :value="isset($exclusion) && $current === 'other' ? $exclusion->reason : ''" />
        <x-input-error :messages="$errors->get('other')" class="mt-2" />
    </div>
    <div @class(['flex justify-end', 'md:!flex-none md:pb-1' => ! empty($inline)])>
        @isset($exclusion)
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        @else
            <x-danger-button>{{ __('Exclude') }}</x-danger-button>
        @endisset
    </div>
</form>
