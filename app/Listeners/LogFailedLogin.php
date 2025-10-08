<?php

namespace App\Listeners;

use App\Models\FailedLoginAttempt;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $credentials = $event->credentials;
        $email = $credentials['email'] ?? 'unknown';
        $ip = request()->ip();
        $userAgent = request()->userAgent();

        // Log to database
        FailedLoginAttempt::logAttempt($email, $ip, $userAgent);

        // Log to file for security monitoring
        Log::channel('security')->warning('Failed login attempt', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Check if this IP or email has too many failed attempts
        $recentAttempts = FailedLoginAttempt::getRecentAttemptsCount($email, $ip);

        if ($recentAttempts >= 5) {
            Log::channel('security')->alert('Multiple failed login attempts detected', [
                'email' => $email,
                'ip' => $ip,
                'attempt_count' => $recentAttempts,
            ]);
        }
    }
}
