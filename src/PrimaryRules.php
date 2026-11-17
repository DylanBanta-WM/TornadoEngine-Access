<?php

namespace TornadoEngine\Access;

use Closure;
use Illuminate\Support\Collection;

/**
 * For import modules' primary rules: each role can have one rule that should cover everyone with
 * that role. After a sync, people another rule gave the role to, but the primary rule missed,
 * are listed (for example a new student not yet in the students' OU).
 */
class PrimaryRules
{
    /**
     * @param  Collection<int, object{id: int, role_id: int, primary: bool}>  $rules  with their role loaded
     * @param  array<int, list<string>>  $matched  rule id => emails it matched
     * @param  array<string, string>  $names  email => name
     * @param  Closure(object): string  $describe  how a rule reads, for example "the OU /Students"
     * @param  array<string, string>  $details  email => a line about them, for example "In OU /"
     * @return array<int, array{role: string, primary: string, people: list<array{email: string, name: string, caught_by: list<string>, detail: ?string}>}>
     */
    public static function missed(Collection $rules, array $matched, array $names, Closure $describe, array $details = []): array
    {
        $missed = [];
        foreach ($rules->where('primary', true) as $primary) {
            $covered = array_flip($matched[$primary->id] ?? []);
            $people = [];
            foreach ($rules->where('role_id', $primary->role_id)->where('id', '!=', $primary->id) as $rule) {
                foreach ($matched[$rule->id] ?? [] as $email) {
                    if (! isset($covered[$email])) {
                        $people[$email] ??= ['email' => $email, 'name' => $names[$email] ?? '', 'caught_by' => [], 'detail' => $details[$email] ?? null];
                        $people[$email]['caught_by'][] = $describe($rule);
                    }
                }
            }
            ksort($people);
            $missed[$primary->role_id] = ['role' => $primary->role->name, 'primary' => $describe($primary), 'people' => array_values($people)];
        }

        return $missed;
    }
}
