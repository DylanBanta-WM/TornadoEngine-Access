<?php

namespace TornadoEngine\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use TornadoEngine\Access\Models\Exclusion;

/**
 * For modules that import people and roles from a directory (Access-Google, Access-AD, …):
 *
 *     $result = app(Imports::class)->apply('google', [$roleId => ['ann@school.org' => 'Ann Teacher']]);
 *
 * Excluded people are left out. People who match get an account and the role; roles this
 * source gave earlier and no longer wants are taken back. Roles given by hand, or by another
 * source, stay.
 */
class Imports
{
    public function __construct(private Access $access) {}

    /** @return array<string, true> excluded addresses */
    public function excluded(): array
    {
        return array_fill_keys(Exclusion::pluck('email')->all(), true);
    }

    /**
     * @param  array<int, array<string, string>>  $wanted  [role id => [email => name]]
     * @return array{gave: int, took_back: int, added: int, people: int}
     */
    public function apply(string $source, array $wanted): array
    {
        $excluded = $this->excluded();
        foreach ($wanted as $roleId => $people) {
            $wanted[$roleId] = array_diff_key(array_change_key_case($people), $excluded);
        }

        $result = DB::transaction(function () use ($source, $wanted) {
            $names = array_merge(...array_values($wanted ?: [[]]));

            // People who match need an account to sign in with.
            $ids = User::whereIn('email', array_keys($names))->pluck('id', 'email')->all();
            $added = 0;
            foreach (array_diff_key($names, $ids) as $email => $name) {
                $user = new User;
                $user->forceFill(['email' => $email, 'name' => $name ?: strstr($email, '@', true)])->save();
                $ids[$email] = $user->id;
                $added++;
            }

            $key = fn ($row) => $row->role_id.':'.$row->user_id;
            $held = DB::table('access_role_user')->get()->keyBy($key);
            $imports = DB::table('access_imported_roles')->get()->groupBy($key);

            $want = [];
            foreach ($wanted as $roleId => $people) {
                foreach (array_keys($people) as $email) {
                    $want[$roleId.':'.$ids[$email]] = ['role_id' => $roleId, 'user_id' => $ids[$email]];
                }
            }

            $gave = 0;
            foreach ($want as $pair => $row) {
                $sources = $imports->get($pair, collect())->pluck('source');
                if (! $held->has($pair)) {
                    DB::table('access_role_user')->insert($row);
                    $gave++;
                } elseif ($sources->isEmpty() || $sources->contains($source)) {
                    continue; // Given by hand (leave it alone), or already ours.
                }
                DB::table('access_imported_roles')->insert($row + ['source' => $source]);
            }

            $tookBack = 0;
            foreach ($imports as $pair => $rows) {
                if (isset($want[$pair]) || ! $rows->contains('source', $source)) {
                    continue;
                }
                $row = $rows->first();
                DB::table('access_imported_roles')->where(['role_id' => $row->role_id, 'user_id' => $row->user_id, 'source' => $source])->delete();
                if ($rows->count() === 1) {
                    DB::table('access_role_user')->where(['role_id' => $row->role_id, 'user_id' => $row->user_id])->delete();
                    $tookBack++;
                }
            }

            return ['gave' => $gave, 'took_back' => $tookBack, 'added' => $added, 'people' => count($names)];
        });

        $this->access->forget();

        return $result;
    }

    /**
     * Leaves someone out of every import, and takes back the roles imports gave them.
     */
    public function exclude(string $email, string $reason, ?User $by = null): Exclusion
    {
        $email = strtolower(trim($email));
        $exclusion = Exclusion::updateOrCreate(['email' => $email], ['reason' => $reason, 'excluded_by' => $by?->id]);

        if ($user = User::where('email', $email)->first()) {
            DB::transaction(function () use ($user) {
                $roles = DB::table('access_imported_roles')->where('user_id', $user->id)->pluck('role_id')->unique();
                DB::table('access_role_user')->where('user_id', $user->id)->whereIn('role_id', $roles)->delete();
                DB::table('access_imported_roles')->where('user_id', $user->id)->delete();
            });
            $this->access->forget();
        }

        return $exclusion;
    }

    /** Whether an import made this person or gave them roles. */
    public static function imported(User $user): bool
    {
        return DB::table('access_imported_roles')->where('user_id', $user->id)->exists();
    }
}
