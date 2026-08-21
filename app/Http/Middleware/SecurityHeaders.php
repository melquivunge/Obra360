<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers.
 *
 * Set here rather than in the web server config because production runs on
 * cPanel/Apache while local development runs on the Docker nginx — putting them
 * in the application is the only way both get them.
 *
 * ponytail: no CSP yet. The Blade views use inline styles and inline event
 * handlers throughout, so a real policy needs a nonce pass over the views first;
 * shipping `unsafe-inline` would be a header that buys nothing.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ];

        // HSTS only over HTTPS — sending it over plain HTTP does nothing and
        // would be wrong for local development.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        return $response;
    }
}
