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
     * Extract user_id from the serialized payload
     * Handles both column value and payload extraction for compatibility
     */
    public function getUserIdAttribute($value)
    {
        // If user_id is already set in column, return it
        if ($value) {
            return $value;
        }

        // Otherwise, extract from payload (Laravel stores it here)
        if (!isset($this->attributes['payload'])) {
            return null;
        }

        try {
            // Decode payload (Laravel base64 encodes it)
            $payload = unserialize(base64_decode($this->attributes['payload']));

            // Try different keys where Laravel might store user_id
            // 'login_web_' . sha1(static class) is the default Laravel auth guard key
            $userId = $payload['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'] ?? null;

            // Fallback to other possible keys
            if (!$userId) {
                $userId = $payload['_auth'] ?? null;
            }

            if (!$userId) {
                // Try to find any key that looks like 'login_web_*'
                foreach ($payload as $key => $val) {
                    if (str_starts_with($key, 'login_web_')) {
                        $userId = $val;
                        break;
                    }
                }
            }

            return $userId;
        } catch (\Exception $e) {
            // Silently fail and return null if payload can't be decoded
            \Log::debug('Failed to extract user_id from session payload: ' . $e->getMessage());
            return null;
        }
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
