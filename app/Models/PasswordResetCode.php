<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetCode extends Model
{
    protected $table = 'password_reset_codes';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'email',
        'requested_role',
        'code_hash',
        'expires_at',
        'verified_at',
        'reset_at',
        'attempts',
        'resend_count',
        'last_sent_at',
        'locked_at',
        'requested_ip',
        'verified_ip',
        'reset_ip',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'reset_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
