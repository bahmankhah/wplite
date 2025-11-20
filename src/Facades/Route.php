<?php

namespace WPLite\Facades;

class Route extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\RouteManager::class;
    }
}