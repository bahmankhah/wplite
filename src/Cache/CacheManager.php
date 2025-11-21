<?php

namespace WPLite\Cache;

use WPLite\Adapters\AdapterManager;


class CacheManager extends AdapterManager
{

    public function getKey(): string{
        return 'cache';
    }

}
