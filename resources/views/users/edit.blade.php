<x-admin-layout :title="__('Users and roles')">
    @include('access::tabs')

    <x-settings-card :title="$user->name">
        <p class="break-all">{{ $user->email }}</p>
        <form method="POST" action="{{ route('access.users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('access::role-checkboxes', ['checked' => $user->roles->pluck('id')->all()])
            @if (\TornadoEngine\Access\Http\UsersController::passwords())
                @include('access::password-fields', ['editing' => true])
            @endif
            <div class="flex justify-end gap-3">
                <x-secondary-link :href="route('access.users.index')">{{ __('Cancel') }}</x-secondary-link>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </div>
        </form>

        @unless ($user->is(auth()->user()))
            <form method="POST" action="{{ route('access.users.destroy', $user) }}" class="border-t border-gray-200 dark:border-gray-700 pt-4"
                  onsubmit="return confirm(@js(__('Remove :email? They won\'t be able to sign in.', ['email' => $user->email])))">
                @csrf
                @method('DELETE')
                <x-danger-button>{{ __('Remove') }}</x-danger-button>
            </form>
        @endunless
    </x-settings-card>
</x-admin-layout>
