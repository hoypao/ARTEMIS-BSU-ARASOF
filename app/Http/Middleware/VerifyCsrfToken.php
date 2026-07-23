<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as Middleware;

/**
 * Accepts the legacy `csrf_token` field name (used by every ported form and
 * fetch() payload) in addition to Laravel's standard `_token`/X-CSRF-TOKEN.
 */
class VerifyCsrfToken extends Middleware
{
    protected function getTokenFromRequest($request)
    {
        return parent::getTokenFromRequest($request) ?: $request->input('csrf_token');
    }
}
