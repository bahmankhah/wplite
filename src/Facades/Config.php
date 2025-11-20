<?php

namespace WPLite\Facades;

class Config extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\Config::class;
    }
}