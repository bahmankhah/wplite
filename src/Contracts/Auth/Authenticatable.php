<?php

namespace WPLite\Contracts\Auth;

/**
 * Authenticatable contract — defines the interface for user models.
 *
 * Role: Any user model used with auth guards must implement these
 *       methods for identifier, password, and remember token access.
 *
 * How to use:
 *   - Implement on your user model class.
 *   - Return instances of this from Guard::user().
 *
 * @see \WPLite\Contracts\Auth\Guard  Uses Authenticatable for user retrieval.
 * @see \WPLite\Auth\GenericUser       Example implementation.
 */
interface Authenticatable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier();

    /**
     * Get the name of the password attribute for the user.
     *
     * @return string
     */
    public function getAuthPasswordName();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken();

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName();
}
