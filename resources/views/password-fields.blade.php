{{-- Website password for someone (when that sign-in method is on). $editing: changing an existing one. --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="password" :value="$editing ? __('New password (optional)') : __('Password')" />
        <x-text-input id="password" class="mt-1 block w-full" type="text" name="password" autocomplete="off" :placeholder="$editing ? '' : \App\Support\SignIn::DEFAULT_PASSWORD" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <label class="flex min-h-11 items-center gap-2 sm:mt-6">
        <input type="hidden" name="must_change_password" value="0">
        <input type="checkbox" name="must_change_password" value="1" class="rounded text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500" @checked(old('must_change_password', $editing ? ($user->must_change_password ?? false) : true))>
        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Must change it at next sign-in') }}</span>
    </label>
</div>
