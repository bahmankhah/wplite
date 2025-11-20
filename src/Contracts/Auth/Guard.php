<?php

namespace WPLite\Contracts\Auth;

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
