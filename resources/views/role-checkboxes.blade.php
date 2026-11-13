{{-- $roles, $checked (list of role ids) --}}
@if ($roles->isEmpty())
    <p class="text-sm text-gray-600 dark:text-gray-400">{!! __('No roles yet. :link first.', ['link' => '<a class="font-medium text-indigo-600 dark:text-indigo-400 underline" href="'.e(route('access.roles.create')).'">'.e(__('Add a role')).'</a>']) !!}</p>
@else
    <fieldset>
        <legend class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Roles') }}</legend>
        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
            @foreach ($roles as $role)
                <label class="flex min-h-11 items-center gap-3 rounded-md border border-gray-200 dark:border-gray-700 px-3 text-sm has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 dark:has-[:checked]:bg-indigo-950/40">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded text-indigo-600 border-gray-300 dark:border-gray-700 dark:bg-gray-900 focus:ring-indigo-500" @checked(in_array($role->id, $checked))>
                    <span class="text-gray-900 dark:text-gray-100">{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('roles')" class="mt-2" />
    </fieldset>
@endif
