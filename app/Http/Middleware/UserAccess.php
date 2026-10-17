<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAccess
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more roles, so `user-access:admin` (every existing route)
     * and `user-access:admin,manager` (shared screens) both work.
     *
     * Denial aborts with 403. The previous implementation returned a JSON body
     * with HTTP 200, which reads as success to browsers, monitoring and tests.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$userTypes): Response
    {
        $user = $request->user();

        abort_if($user === null, 403, 'You do not have permission to access this page.');
        abort_unless(in_array($user->type, $userTypes, true), 403, 'You do not have permission to access this page.');

        return $next($request);
    }
}
