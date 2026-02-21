<?php

namespace WPLite;

/**
 * RouteManager — the router that creates and groups route definitions.
 *
 * Role: Provides the DSL for defining routes in typed groups (rest, ajax,
 *       admin, web). Each call to get()/post() returns a RouteDefinition.
 *
 * Responsibilities:
 *   - Create RouteDefinition instances for GET and POST routes.
 *   - Group routes by type (rest, ajax, admin, web).
 *   - Manage named routes for URL generation via reverse().
 *   - Load route files from the filesystem.
 *
 * How to use:
 *   - Access via the Route facade in route files (routes/*.php):
 *     Route::rest(function ($route) { $route->get('/path', [Ctrl::class, 'method']); });
 *   - Use ->name('route.name') for named routes, then reverse('route.name').
 *
 * Avoid:
 *   - Do not instantiate RouteManager directly; use the Route facade.
 *   - Do not define routes outside of route files (routes/*.php).
 *
 * @see \WPLite\RouteDefinition              Individual route objects.
 * @see \WPLite\Providers\RouteServiceProvider Loads route files.
 * @see \WPLite\Facades\Route                  Facade for this class.
 */
class RouteManager
{
    public function loadRoutesFile($file)
    {
        if (file_exists($file)) {
            include $file;
        }
    }
    private $type = 'rest';
    public function __construct($type = 'rest'){
        $this->type = $type;
    }
    public $names = [];
    public function setName(RouteDefinition $route, string $name): void
    {
        if (isset($this->names[$name])) {
            throw new \Exception("Route name '{$name}' already exists.");
        }
        $this->names[$name] = $route;
    }
    public function getName(string $name): ?RouteDefinition
    {
        if (isset($this->names[$name])) {
            return $this->names[$name];
        }
        return null;
    }

    public function ajax($callback){
        $routing = new RouteManager('ajax');
        $callback($routing);
    }
    public function rest($callback){
        $routing = new RouteManager('rest');
        $callback($routing);
    }

    public function web($callback){
        $routing = new RouteManager('web');
        $callback($routing);
    }

    public function admin($callback){
        $routing = new RouteManager('admin');
        $callback($routing);
    }

    public function get(string $route, array $callable): RouteDefinition
    {
        return new RouteDefinition('GET', $route, $callable, $this->type);
    }

    public function post(string $route, array $callable): RouteDefinition
    {
        return new RouteDefinition('POST', $route, $callable, $this->type);
    }

}
