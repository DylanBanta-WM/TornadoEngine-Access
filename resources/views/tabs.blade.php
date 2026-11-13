<div class="flex flex-wrap gap-2">
    @foreach (app(\TornadoEngine\Access\Access::class)->tabs() as $route => ['label' => $label, 'active' => $active])
        @php($on = request()->routeIs($active))
        <a href="{{ route($route) }}" @if ($on) aria-current="page" @endif
           @class([
               'inline-flex min-h-11 items-center rounded-md px-4 text-sm font-medium',
               'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300' => $on,
               'text-gray-600 hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100' => ! $on,
           ])>{{ $label }}</a>
    @endforeach
</div>

@if (session('status'))
    <p class="rounded-md bg-green-50 dark:bg-green-900/30 px-4 py-3 text-sm text-green-800 dark:text-green-300">{{ session('status') }}</p>
@endif
