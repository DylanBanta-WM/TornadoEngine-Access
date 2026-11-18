<?php

namespace TornadoEngine\Access\Tests;

use App\Models\Setting;
use App\Models\User;
use App\Support\GoogleSignIn;
use App\Support\SignIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;
use TornadoEngine\Access\Access;
use TornadoEngine\Access\Imports;
use TornadoEngine\Access\Models\Exclusion;
use TornadoEngine\Access\Models\PatternRule;
use TornadoEngine\Access\Models\Role;
use TornadoEngine\Nav\Nav;
use TornadoEngine\Nav\NavGroup;

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

    public function test_users_and_roles_is_in_the_administration_dropdown_for_managers(): void
    {
        $labels = function (?User $user) {
            $group = collect(app(Nav::class)->for($user))->first(fn ($entry) => $entry instanceof NavGroup && $entry->key === 'administration');

            return $group ? array_map(fn ($item) => $item->label, $group->items) : [];
        };

        $this->assertContains('Users and roles', $labels($this->owner()));
        $this->assertNotContains('Users and roles', $labels(User::factory()->create()));
    }

    public function test_imports_from_two_sources_only_take_back_their_own_roles(): void
    {
        $imports = app(Imports::class);
        $staff = Role::firstOrCreate(['name' => 'Staff']);
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
        $staff = Role::firstOrCreate(['name' => 'Staff']);
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
        SignIn::save(['google', 'password']);
        $owner = $this->owner();

        $this->actingAs($owner)->get('/access/users')->assertSee('ChangeMe!')->assertSee('Must change it at next sign-in');
        $this->actingAs($owner)->post('/access/users', ['email' => 'kim@elsewhere.test', 'must_change_password' => '1']);
        $kim = User::where('email', 'kim@elsewhere.test')->sole();
        $this->assertTrue(Hash::check('ChangeMe!', $kim->password));
        $this->assertTrue($kim->must_change_password);

        $this->actingAs($owner)->post('/access/users', ['email' => 'lee@school.test', 'password' => 'chosen-password', 'must_change_password' => '0']);
        $lee = User::where('email', 'lee@school.test')->sole();
        $this->assertTrue(Hash::check('chosen-password', $lee->password));
        $this->assertFalse($lee->must_change_password);

        $this->actingAs($owner)->put("/access/users/{$lee->id}", ['password' => 'reset-password', 'must_change_password' => '1']);
        $this->assertTrue(Hash::check('reset-password', $lee->fresh()->password));
        $this->assertTrue($lee->fresh()->must_change_password);
    }

    public function test_without_website_passwords_there_are_no_password_fields(): void
    {
        SignIn::save(['google']);
        $owner = $this->owner();

        $this->actingAs($owner)->get('/access/users')->assertDontSee('ChangeMe!');
        $this->actingAs($owner)->post('/access/users', ['email' => 'kim@school.test', 'password' => 'ignored-password']);
        $this->assertNull(User::where('email', 'kim@school.test')->value('password'));
    }

    public function test_the_profile_shows_how_someone_signs_in_their_roles_and_what_they_can_open(): void
    {
        $teachers = $this->role('Teachers', ['access.manage']);
        $library = $this->role('Library', []);
        app(Imports::class)->apply('google', [$teachers->id => ['kim@school.test' => 'Kim Teacher']]);
        $kim = User::where('email', 'kim@school.test')->sole();
        $kim->forceFill(['google_id' => 'g-1'])->save();
        $kim->roles()->attach($library);

        $this->actingAs($kim)->get('/profile')
            ->assertOk()
            ->assertSeeInOrder(['Signs in with:', 'Google'])
            ->assertSeeInOrder(['Library', 'Given by hand', 'Teachers', 'Google'])
            ->assertSee('Users and roles')
            ->assertSee('Manage users and roles')
            ->assertDontSee('Change password');

        $this->actingAs(User::factory()->create(['password' => Hash::make('secret-pass')]))->get('/profile')
            ->assertSeeInOrder(['Signs in with:', 'Website password'])->assertSee('No roles yet.')->assertSee('Change password');
        $this->get('/profile');
    }

    public function test_admin_pages_are_in_the_administration_dropdown_and_the_user_menu_has_profile(): void
    {
        $this->actingAs($this->owner())->get('/dashboard')
            ->assertSeeInOrder(['Administration', 'Administration', 'Users and roles', 'Modules'])
            ->assertSee(route('profile'));
    }

    public function test_every_district_starts_with_admin_staff_and_student(): void
    {
        $this->assertSame(['Admin', 'Staff', 'Student'], Role::orderBy('name')->pluck('name')->all());
        $this->assertTrue(Role::where('name', 'Admin')->value('all_permissions'));
    }

    public function test_a_role_with_every_permission_gets_ones_added_later(): void
    {
        $kim = User::factory()->create();
        $kim->roles()->attach(Role::where('name', 'Admin')->value('id'));
        app(Access::class)->permission('zz.later', 'Added later', module: 'Later');

        $this->assertTrue($kim->can('zz.later'));
        $this->assertTrue($kim->can('access.manage'));
        $this->assertFalse(User::factory()->create()->can('zz.later'));
    }

    public function test_email_patterns_give_roles_to_everyone_who_matches(): void
    {
        $owner = $this->owner();
        $student = Role::where('name', 'Student')->first();
        $kid = User::factory()->create(['email' => 'wm30kid@school.test']);
        User::factory()->create(['email' => 'teacher@school.test']);

        $this->actingAs($owner)->post('/access/patterns', ['role_id' => $student->id, 'pattern' => '^wm\\d{2}[a-z]+@school\\.test$'])->assertSessionHasNoErrors();
        $this->assertSame(['Student'], $kid->roles()->pluck('name')->all());
        $this->assertSame([], User::where('email', 'teacher@school.test')->first()->roles()->pluck('name')->all());

        // People added later get it too, by hand or from an import.
        $this->actingAs($owner)->post('/access/users', ['email' => 'wm31new@school.test']);
        $this->assertSame(['Student'], User::where('email', 'wm31new@school.test')->first()->roles()->pluck('name')->all());
        app(Imports::class)->apply('google', [Role::where('name', 'Staff')->value('id') => ['wm32imp@school.test' => 'Imp']]);
        $this->assertSame(['Staff', 'Student'], User::where('email', 'wm32imp@school.test')->first()->roles()->orderBy('name')->pluck('name')->all());

        $this->actingAs($owner)->get('/access/patterns')->assertSee('^wm\\d{2}[a-z]+@school\\.test$', false)->assertSee('3 people');
        $this->actingAs($owner)->from('/access/patterns')->post('/access/patterns', ['role_id' => $student->id, 'pattern' => '(unclosed'])->assertSessionHasErrors('pattern');

        // Removing the rule takes the role back.
        $this->actingAs($owner)->delete('/access/patterns/'.PatternRule::sole()->id);
        $this->assertSame([], $kid->roles()->pluck('name')->all());
    }

    public function test_the_setup_admin_gets_the_admin_role(): void
    {
        $owner = $this->owner();

        $this->assertSame(['Admin'], $owner->roles()->pluck('name')->all());
        $this->assertSame([], User::factory()->create()->roles()->pluck('name')->all());
        $this->actingAs($owner)->get('/access/roles')->assertSeeInOrder(['Admin', '1'])->assertDontSee('Everything');
        $this->actingAs($owner)->get('/access/users')->assertDontSee('Setup admin');
    }

    public function test_the_admin_role_keeps_every_permission_and_cant_be_removed(): void
    {
        $owner = $this->owner();
        $admin = Role::admin();

        $this->actingAs($owner)->get("/access/roles/{$admin->id}/edit")->assertSee('disabled', false)->assertDontSee('Remove role');
        $this->actingAs($owner)->put("/access/roles/{$admin->id}", ['name' => 'Admin', 'all_permissions' => '0']);
        $this->assertTrue($admin->fresh()->all_permissions);
        $this->actingAs($owner)->delete("/access/roles/{$admin->id}")->assertForbidden();
        $this->assertNotNull($admin->fresh());

        // Other roles can still have it turned off.
        $other = Role::create(['name' => 'Helpers', 'all_permissions' => true]);
        $this->actingAs($owner)->put("/access/roles/{$other->id}", ['name' => 'Helpers', 'all_permissions' => '0']);
        $this->assertFalse($other->fresh()->all_permissions);
    }

    public function test_the_users_list_can_be_searched_filtered_and_paged(): void
    {
        $owner = tap($this->owner())->update(['name' => 'Zz Owner']); // last by name
        $teachers = Role::create(['name' => 'Teachers']);
        foreach (range(1, 12) as $i) {
            $user = User::factory()->create(['name' => sprintf('Person %02d', $i), 'email' => "p{$i}@school.test", 'password' => null, 'google_id' => $i <= 3 ? "g{$i}" : null]);
            if ($i % 2 === 0) {
                $user->roles()->attach($teachers);
            }
        }

        $this->actingAs($owner)->get('/access/users?per_page=10')->assertOk()->assertSee('Person 10')->assertDontSee('Person 11')->assertSee('Showing 1–10 of 13');
        $this->actingAs($owner)->get('/access/users?page=2')->assertSee('Person 11')->assertDontSee('Person 10', false); // the page size is remembered
        $this->actingAs($owner)->get('/access/users?q=p7%40')->assertSee('Person 07')->assertDontSee('Person 08');
        $this->actingAs($owner)->get('/access/users?roles[]='.$teachers->id)->assertSee('Person 02')->assertDontSee('Person 03')->assertSee('Showing 1–6 of 6');
        $this->actingAs($owner)->get('/access/users?roles[]=none&per_page=25')->assertSee('Person 03')->assertDontSee('Person 02');
        $this->actingAs($owner)->get('/access/users?sign_in=google')->assertSee('Person 03')->assertDontSee('Person 04');
        $this->actingAs($owner)->get('/access/users?per_page=7')->assertSessionHasErrors('per_page');
    }
}
