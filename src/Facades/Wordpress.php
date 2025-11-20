<?php

namespace WPLite\Facades;

class Wordpress extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\WordpressManager::class;
    }
}