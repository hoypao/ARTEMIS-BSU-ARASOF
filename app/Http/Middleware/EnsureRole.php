<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel port of the legacy require_role()/require_login() guards
 * (includes/auth.php): no session -> login page; wrong role -> that
 * user's own dashboard.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!is_logged_in()) {
            return redirect()->route('login');
        }

        $user = current_user();
        if (!in_array($user['role'], $roles, true)) {
            return redirect()->route(dashboard_route_for_role($user['role']));
        }

        return $next($request);
    }
}
