<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            if ($request->routeIs('logout')) {
                return $next($request);
            }

            if ($user->isPending()) {
                if (! $request->routeIs('activation.pending')) {
                    return redirect()->route('activation.pending');
                }
            } else {
                if (! $request->routeIs('activation.inactive')) {
                    return redirect()->route('activation.inactive');
                }
            }
        }

        return $next($request);
    }
}
