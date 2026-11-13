# Tornado Engine Access

A [Tornado Engine](https://github.com/DylanBanta-WM/TornadoEngineCore) core-module: the people who can sign in, roles, and which pages each person can see. It's under **Users and roles** in the menu under your name.

The account made during first-time setup always has every permission.

## Permissions in a module

Declare your module's permissions in your service provider's `boot()`:

```php
use TornadoEngine\Access\Access;

$this->callAfterResolving(Access::class, function (Access $access) {
    $access->permission('notes.view', 'See notes', module: 'Notes');
});
```

Then check them the usual Laravel way: the `can:notes.view` route middleware, `@can` in Blade, or `$user->can('notes.view')`. Nav menu items take `can: 'notes.view'`.

Admins choose which roles get each permission. Define a fallback with `Gate::define()` for when Access isn't installed.
