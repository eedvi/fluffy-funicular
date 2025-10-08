<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedLoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    /**
     * Scope to get recent attempts.
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope to get attempts by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to get attempts by IP.
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Get failed attempts count for email and IP in last hour.
     */
    public static function getRecentAttemptsCount(string $email, string $ip): int
    {
        return static::recent(60)
            ->where(function ($query) use ($email, $ip) {
                $query->where('email', $email)
                    ->orWhere('ip_address', $ip);
            })
            ->count();
    }

    /**
     * Log a failed login attempt.
     */
    public static function logAttempt(string $email, string $ip, ?string $userAgent = null): void
    {
        static::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'attempted_at' => now(),
        ]);
    }
}
