<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        $configured = env('TRUSTED_PROXIES');

        if (is_string($configured) && $configured !== '') {
            $this->proxies = array_map('trim', explode(',', $configured));
            return;
        }

        // Managed platforms (Railway/Heroku-style) terminate TLS at their edge;
        // without this, request IPs and HTTPS detection would be wrong.
        $this->proxies = app()->environment('production') ? ['*'] : null;
    }
}
