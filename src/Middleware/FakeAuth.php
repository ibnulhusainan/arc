<?php

namespace IbnulHusainan\Arc\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * FakeAuth Middleware
 *
 * Automatically authenticates a fake user when no user
 * is currently logged in.
 *
 * This middleware is intended for:
 * - Local development
 * - Demo environments
 * - Testing UI flows without a real authentication process
 *
 * ⚠️ WARNING:
 * This middleware MUST NOT be enabled in production environments.
 *
 * Behavior:
 * - If no authenticated user exists, a default user record
 *   will be created (if not already present).
 * - The user will then be logged in automatically.
 */
class FakeAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $User = config('auth.providers.users.model');

        if (!Auth::check()) {
            $user = $User::firstOrCreate(
                ['email' => 'fake@example.com'],
                [
                    'name'     => 'Fake User',
                    'password' => bcrypt('password'),
                ]
            );

            Auth::login($user);
        }

        return $next($request);
    }
}
