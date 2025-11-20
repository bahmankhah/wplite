<?php

namespace App\Auth\Guards;

use WPLite\Contracts\Auth\Authenticatable;

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