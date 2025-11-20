<?php

namespace WPLite\Facades;

class View extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\ViewManager::class;
    }
}