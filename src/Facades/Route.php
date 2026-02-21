<?php

namespace WPLite\Facades;

/**
 * Route facade — static access to the RouteManager.
 *
 * @method static void rest(callable $callback) Define REST API routes
 * @method static void ajax(callable $callback) Define Ajax routes
 * @method static void admin(callable $callback) Define admin page routes
 * @method static void web(callable $callback) Define web (frontend) routes
 * @method static \WPLite\RouteDefinition get(string $route, array $callable) Register a GET route
 * @method static \WPLite\RouteDefinition post(string $route, array $callable) Register a POST route
 * @method static void loadRoutesFile(string $file) Load a route definitions file
 * @method static void setName(\WPLite\RouteDefinition $route, string $name) Register a named route
 * @method static \WPLite\RouteDefinition|null getName(string $name) Retrieve a named route
 *
 * @see \WPLite\RouteManager
 */
class Route extends Facade{

    protected static function getFacadeAccessor() {
        return \WPLite\RouteManager::class;
    }
}