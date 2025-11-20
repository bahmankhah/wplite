<?php

namespace WPLite\Auth;

use WPLite\Adapters\AdapterManager;


class AuthManager extends AdapterManager
{

    public function getKey(): string{
        return 'auth';
    }

}
