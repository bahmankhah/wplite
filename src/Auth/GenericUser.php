<?php

namespace App\Auth\Guards;

use WPLite\Contracts\Auth\Authenticatable;

/**
 * GenericUser — example Authenticatable implementation.
 *
 * Role: A skeleton user model that implements the Authenticatable contract.
 *       This is provided as a starting point; override methods with real logic.
 *
 * Note: This file uses the App\Auth\Guards namespace because it belongs
 *       to the consumer plugin's code, not the WPLite framework itself.
 *       It is included in the framework source as a reference example.
 *
 * @see \WPLite\Contracts\Auth\Authenticatable  Interface this implements.
 */
class GenericUser implements Authenticatable{

    public function getAuthIdentifierName()
    {
        // implementation
    }

    public function getAuthIdentifier()
    {
        // implementation
    }

    public function getAuthPasswordName()
    {
        // implementation
    }

    public function getAuthPassword()
    {
        // implementation
    }

    public function getRememberToken()
    {
        // implementation
    }

    public function setRememberToken($value)
    {
        // implementation
    }

    public function getRememberTokenName()
    {
        // implementation
    }
}