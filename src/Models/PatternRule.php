<?php

namespace TornadoEngine\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gives a role to everyone with a Tornado Engine account whose email address matches a regular
 * expression (case doesn't matter), for example ^wm\d{2}[a-z]+@district\.org$ for students.
 */
class PatternRule extends Model
{
    protected $table = 'access_pattern_rules';

    protected $fillable = ['role_id', 'pattern', 'matched'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function matches(string $email): bool
    {
        return @preg_match('/'.str_replace('/', '\/', $this->pattern).'/i', $email) === 1;
    }

    public static function valid(string $pattern): bool
    {
        return @preg_match('/'.str_replace('/', '\/', $pattern).'/i', '') !== false;
    }

    protected function casts(): array
    {
        return ['matched' => 'integer'];
    }
}
