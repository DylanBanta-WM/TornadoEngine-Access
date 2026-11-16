<?php

namespace TornadoEngine\Access\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone every import leaves out: no account made, no roles given, and imported roles taken back.
 */
class Exclusion extends Model
{
    public const REASONS = ['Employment ended', 'Service account'];

    protected $table = 'access_exclusions';

    protected $fillable = ['email', 'reason', 'excluded_by'];

    public function excludedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'excluded_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
