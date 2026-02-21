<?php

namespace WPLite\Facades;

/**
 * View facade — static access to the ViewManager.
 *
 * @method static void render(string $view, array $data = []) Render a view template
 *
 * Note: The view() helper function is a shortcut for View::render().
 *
 * @see \WPLite\ViewManager
 */
class View extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\ViewManager::class;
    }
}