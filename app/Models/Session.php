<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'integer',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session's last activity as a datetime.
     */
    public function getLastActivityAttribute($value)
    {
        return \Carbon\Carbon::createFromTimestamp($value);
    }

    /**
     * Check if session is currently active (within last 5 minutes)
     */
    public function isActive(): bool
    {
        return $this->last_activity->greaterThan(now()->subMinutes(5));
    }

    /**
     * Get browser name from user agent
     */
    public function getBrowserAttribute(): string
    {
        $agent = $this->user_agent;

        if (str_contains($agent, 'Firefox')) return 'Firefox';
        if (str_contains($agent, 'Edg')) return 'Edge';
        if (str_contains($agent, 'Chrome')) return 'Chrome';
        if (str_contains($agent, 'Safari')) return 'Safari';
        if (str_contains($agent, 'Opera')) return 'Opera';

        return 'Unknown';
    }

    /**
     * Get device type from user agent
     */
    public function getDeviceAttribute(): string
    {
        $agent = $this->user_agent;

        if (str_contains($agent, 'Mobile')) return 'Mobile';
        if (str_contains($agent, 'Tablet')) return 'Tablet';

        return 'Desktop';
    }
}
