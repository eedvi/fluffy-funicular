<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Force no-cache for authentication pages to prevent POST method errors
        // This is CRITICAL to prevent Cloudflare/CDN from caching login pages
        if ($request->is('admin/login*') ||
            $request->is('admin/register*') ||
            $request->is('admin/password*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT');
            // Tell Cloudflare to NEVER cache this
            $response->headers->set('CDN-Cache-Control', 'no-store');
            $response->headers->set('Cloudflare-CDN-Cache-Control', 'no-store');
            // Vary to prevent shared cache
            $response->headers->set('Vary', 'Cookie');
        }

        // Only apply security headers if enabled in config
        if (config('security.headers_enabled', true)) {
            // HTTP Strict Transport Security (HSTS)
            if (config('security.hsts_enabled', true)) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            }

            // Content Security Policy (CSP)
            if (config('security.csp_enabled', true)) {
                $csp = implode('; ', [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
                    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                    "font-src 'self' https://fonts.gstatic.com data:",
                    "img-src 'self' data: https:",
                    "connect-src 'self'",
                    "frame-ancestors 'none'",
                ]);
                $response->headers->set('Content-Security-Policy', $csp);
            }

            // X-Content-Type-Options
            $response->headers->set('X-Content-Type-Options', 'nosniff');

            // X-Frame-Options
            $frameOptions = config('security.frame_options', 'DENY');
            $response->headers->set('X-Frame-Options', $frameOptions);

            // X-XSS-Protection
            if (config('security.xss_protection', true)) {
                $response->headers->set('X-XSS-Protection', '1; mode=block');
            }

            // Referrer-Policy
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

            // Permissions-Policy
            $permissionsPolicy = implode(', ', [
                'geolocation=()',
                'microphone=()',
                'camera=()',
            ]);
            $response->headers->set('Permissions-Policy', $permissionsPolicy);
        }

        return $response;
    }
}
