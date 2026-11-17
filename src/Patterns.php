<?php

namespace TornadoEngine\Access;

use App\Models\User;
use TornadoEngine\Access\Models\PatternRule;

/**
 * Applies the email pattern rules to everyone with an account (an import's source "email-pattern").
 * They run when rules change, when people are added by hand, and after every import.
 */
class Patterns
{
    public const SOURCE = 'email-pattern';

    public function __construct(private Imports $imports) {}

    public function apply(): void
    {
        $rules = PatternRule::all();
        $users = User::pluck('name', 'email')->all();

        $wanted = [];
        foreach ($rules as $rule) {
            $matched = array_filter($users, fn ($name, $email) => $rule->matches($email), ARRAY_FILTER_USE_BOTH);
            $rule->update(['matched' => count($matched)]);
            $wanted[$rule->role_id] = array_merge($wanted[$rule->role_id] ?? [], $matched);
        }

        $this->imports->apply(self::SOURCE, $wanted);
    }
}
