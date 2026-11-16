<?php

namespace TornadoEngine\Access\Tests;

use App\Models\Setting;
use App\Models\User;
use App\Support\GoogleSignIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;
use TornadoEngine\Access\Access;
use TornadoEngine\Access\Imports;
use TornadoEngine\Access\Models\Exclusion;
use TornadoEngine\Access\Models\Role;
use TornadoEngine\Nav\Nav;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('google.domain', 'school.test');
        Setting::put('google.client_id', 'id');
        Setting::putSecret('google.client_secret', 'secret');
    }

    private function owner(): User
    {
        return User::factory()->create(['is_admin' => true, 'email' => 'it@school.test']);
    }

    private function role(string $name, array $permissions): Role
    {
        $role = Role::create(['name' => $name]);
        $role->setPermissions($permissions);

        return $role;
    }

    public function test_the_setup_admin_has_every_permission(): void
    {
        $owner = $this->owner();

        $this->assertTrue($owner->can('administration'));
        $this->assertTrue($owner->can('access.manage'));
        $this->actingAs($owner)->get('/access/users')->assertOk();
    }

    public function test_people_get_permissions_only_through_their_roles(): void
    {
        $staff = User::factory()->create();
        $this->assertFalse($staff->can('administration'));
        $this->actingAs($staff)->get('/administration')->assertForbidden();
        $this->actingAs($staff)->get('/access/users')->assertForbidden();

        $staff->roles()->attach($this->role('Tech staff', ['administration']));
        app(Access::class)->forget();

        $this->actingAs($staff)->get('/administration')->assertOk();
        $this->actingAs($staff)->get('/access/users')->assertForbidden();
    }

    public function test_added_people_can_sign_in_with_google(): void
    {
        $role = $this->role('Teachers', []);

        $this->actingAs($this->owner())
            ->post('/access/users', ['email' => 'Pat@School.test', 'roles' => [$role->id]])
            ->assertRedirect('/access/users');

        $pat = User::where('email', 'pat@school.test')->firstOrFail();
        $this->assertSame([$role->id], $pat->roles->pluck('id')->all());

        auth()->logout();
        $account = (new GoogleUser)
            ->setRaw(['email_verified' => true, 'hd' => 'school.test'])
            ->map(['id' => 'g-pat', 'email' => 'pat@school.test', 'name' => 'Pat Teacher']);
        $this->mock(GoogleSignIn::class)->shouldReceive('user')->andReturn($account);

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($pat);
        $this->assertSame('Pat Teacher', $pat->fresh()->name);
    }

    public function test_only_the_sign_in_domain_can_be_added(): void
    {
        $this->actingAs($this->owner())
            ->post('/access/users', ['email' => 'someone@gmail.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'someone@gmail.com']);
    }

    public function test_roles_can_change_and_people_can_be_removed(): void
    {
        $owner = $this->owner();
        $pat = User::factory()->create();
        $role = $this->role('Tech staff', ['access.manage']);

        $this->actingAs($owner)->put("/access/users/{$pat->id}", ['roles' => [$role->id]])->assertRedirect('/access/users');
        $this->assertTrue($pat->fresh()->roles->contains($role));

        $this->actingAs($owner)->delete("/access/users/{$pat->id}")->assertRedirect('/access/users');
        $this->assertNull($pat->fresh());
    }

    public function test_the_setup_admin_and_yourself_cannot_be_removed(): void
    {
        $owner = $this->owner();
        $manager = User::factory()->create();
        $manager->roles()->attach($this->role('Managers', ['access.manage']));

        $this->actingAs($manager)->delete("/access/users/{$owner->id}")->assertForbidden();
        $this->actingAs($manager)->delete("/access/users/{$manager->id}")->assertForbidden();
    }

    public function test_roles_hold_known_permissions_only(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/access/roles', ['name' => 'Tech staff', 'permissions' => ['administration']])
            ->assertRedirect('/access/roles');
        $this->assertSame(['administration'], Role::where('name', 'Tech staff')->first()->permissions());

        $this->actingAs($owner)
            ->post('/access/roles', ['name' => 'Odd', 'permissions' => ['no.such.permission']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_users_and_roles_is_in_the_user_menu_for_managers(): void
    {
        $labels = fn (?User $user) => array_map(fn ($item) => $item->label, app(Nav::class)->userItemsFor($user));

        $this->assertContains('Users and roles', $labels($this->owner()));
        $this->assertNotContains('Users and roles', $labels(User::factory()->create()));
    }

    public function test_imports_from_two_sources_only_take_back_their_own_roles(): void
    {
        $imports = app(Imports::class);
        $staff = Role::create(['name' => 'Staff']);
        $library = Role::create(['name' => 'Library']);
        $byHand = User::factory()->create(['email' => 'kim@school.test']);
        $byHand->roles()->attach($library);

        $result = $imports->apply('google', [$staff->id => ['Ann@school.test' => 'Ann Teacher'], $library->id => ['kim@school.test' => 'Kim']]);
        $this->assertSame(['gave' => 1, 'took_back' => 0, 'added' => 1, 'people' => 2], $result);
        $imports->apply('ad', [$staff->id => ['ann@school.test' => 'Ann Teacher']]);
        $ann = User::where('email', 'ann@school.test')->first();
        $this->assertSame('Ann Teacher', $ann->name);

        // Google drops Ann: AD still gives her Staff. Google never had Kim's Library role (given by hand).
        $imports->apply('google', []);
        $this->assertTrue($ann->roles()->where('name', 'Staff')->exists());
        $this->assertTrue($byHand->roles()->where('name', 'Library')->exists());

        // AD drops her too: now it's taken back.
        $this->assertSame(1, $imports->apply('ad', [])['took_back']);
        $this->assertFalse($ann->roles()->exists());
    }

    public function test_excluded_people_are_left_out_of_imports_and_moved_to_the_excluded_tab(): void
    {
        $imports = app(Imports::class);
        $owner = $this->owner();
        $staff = Role::create(['name' => 'Staff']);
        $imports->apply('google', [$staff->id => ['ann@school.test' => 'Ann Teacher']]);

        $this->actingAs($owner)->get('/access/users')->assertSeeInOrder(['Ann Teacher', 'Exclude', 'Edit']);
        $this->actingAs($owner)->post('/access/exclusions', ['email' => 'ann@school.test', 'reason' => 'Employment ended']);

        $ann = User::where('email', 'ann@school.test')->first();
        $this->assertFalse($ann->roles()->exists());
        $this->assertSame(0, $imports->apply('google', [$staff->id => ['ann@school.test' => 'Ann Teacher']])['gave']);
        $this->actingAs($owner)->get('/access/users')->assertDontSee('Ann Teacher');
        $this->actingAs($owner)->get('/access/exclusions')->assertSeeInOrder(['Ann Teacher', 'ann@school.test', 'Employment ended', 'Change reason', 'Edit', 'Un-exclude']);

        $exclusion = Exclusion::sole();
        $this->actingAs($owner)->put("/access/exclusions/{$exclusion->id}", ['reason' => 'other', 'other' => 'On leave']);
        $this->assertSame('On leave', $exclusion->fresh()->reason);
        $this->actingAs($owner)->from('/access/exclusions')->post('/access/exclusions', ['email' => 'svc@school.test', 'reason' => 'other'])->assertSessionHasErrors('other');

        $this->actingAs($owner)->delete("/access/exclusions/{$exclusion->id}");
        $this->assertSame(1, $imports->apply('google', [$staff->id => ['ann@school.test' => 'Ann Teacher']])['gave']);
        $this->actingAs($owner)->get('/access/users')->assertSee('ann@school.test');
        $this->actingAs(User::factory()->create())->get('/access/exclusions')->assertForbidden();
    }

    public function test_with_website_passwords_new_people_get_one_to_change(): void
    {
        \App\Support\SignIn::save(['google', 'password']);
        $owner = $this->owner();

        $this->actingAs($owner)->get('/access/users')->assertSee('ChangeMe!')->assertSee('Must change it at next sign-in');
        $this->actingAs($owner)->post('/access/users', ['email' => 'kim@elsewhere.test', 'must_change_password' => '1']);
        $kim = User::where('email', 'kim@elsewhere.test')->sole();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('ChangeMe!', $kim->password));
        $this->assertTrue($kim->must_change_password);

        $this->actingAs($owner)->post('/access/users', ['email' => 'lee@school.test', 'password' => 'chosen-password', 'must_change_password' => '0']);
        $lee = User::where('email', 'lee@school.test')->sole();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('chosen-password', $lee->password));
        $this->assertFalse($lee->must_change_password);

        $this->actingAs($owner)->put("/access/users/{$lee->id}", ['password' => 'reset-password', 'must_change_password' => '1']);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('reset-password', $lee->fresh()->password));
        $this->assertTrue($lee->fresh()->must_change_password);
    }

    public function test_without_website_passwords_there_are_no_password_fields(): void
    {
        \App\Support\SignIn::save(['google']);
        $owner = $this->owner();

        $this->actingAs($owner)->get('/access/users')->assertDontSee('ChangeMe!');
        $this->actingAs($owner)->post('/access/users', ['email' => 'kim@school.test', 'password' => 'ignored-password']);
        $this->assertNull(User::where('email', 'kim@school.test')->value('password'));
    }
}
