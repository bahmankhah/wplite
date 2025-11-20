<?php
namespace WPLite;

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
