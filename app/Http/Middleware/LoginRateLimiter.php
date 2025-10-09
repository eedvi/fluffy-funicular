<?php

namespace App\Http\Middleware;

use App\Models\FailedLoginAttempt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check on login attempts
        if ($request->is('admin/login') && $request->isMethod('POST')) {
            $email = $request->input('email', 'unknown');
            $ip = $request->ip();

            // Check recent failed attempts
            $recentAttempts = FailedLoginAttempt::getRecentAttemptsCount($email, $ip);

            // Block if 5 or more failed attempts in last hour
            if ($recentAttempts >= 5) {
                return response()->json([
                    'message' => 'Demasiados intentos fallidos. Por favor intenta nuevamente en 1 hora.',
                ], 429);
            }
        }

        return $next($request);
    }
}
