<?php

namespace WPLite\Contracts\Auth;

/**
 * Guard contract — defines the interface for authentication guards.
 *
 * Role: Any authentication strategy (SSO, JWT, session, etc.) must
 *       implement these methods to integrate with the Auth facade.
 *
 * How to use:
 *   - Implement this interface + extend Adapter for new auth guards.
 *   - Register in configs/adapters.php under the 'auth' key.
 *
 * @see \WPLite\Auth\Guards\SSOGuard  Built-in SSO/OAuth2 guard.
 * @see \WPLite\Facades\Auth           Facade for authentication.
 */
interface Guard
{
    /**
     * Check if the user is authenticated.
     *
     * @return bool
     */
    public function check(): bool;

    /**
     * Get the currently authenticated user.
     *
     * @return mixed
     */
    public function user();

    /**
     * Log the user into the application.
     *
     * @param  mixed  $user
     * @return void
     */
    public function login($user);

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout();

    /**
     * Attempt to authenticate the user using the given credentials.
     *
     * @param  string  $identifier
     * @param  string  $password
     * @return mixed
     */
    public function attempt(array $credential);
}
