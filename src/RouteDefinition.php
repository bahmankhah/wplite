<?php
namespace WPLite;

use WPLite\Facades\Route;

class RouteDefinition
{
    private $middlewares = [];
    private $callable;
    private $route;
    private $type;
    private $method;
    private $useNamespace = true;

    public function __construct(string $method, string $route, array $callable, string $type = 'rest')
    {
        $this->method = $method;
        $this->route = $route;
        $this->callable = $callable;
        $this->type = $type;
    }

    public function withoutNamespace(): self
    {
        if($this->type !== 'rest'){
            return $this;
        }
        $this->useNamespace = false;
        return $this;
    }

    public function middleware(...$args)
    {
        foreach ($args as $arg) {
            $this->middlewares[] = $arg;
        }
        return $this;
    }

    private function generateDynamicRoute(string $route): string
    {
        $pattern = '/\{([a-zA-Z0-9_]+)\}/';

        return preg_replace_callback($pattern, function ($matches) {
            return '(?P<' . $matches[1] . '>\w+)';
        }, $route);
    }
    public function name(string $name): self
    {
        Route::setName($this, $name);
        return $this;
    }

    public function buildRoute(array $params = []): string
    {
        $route = $this->route;
        foreach ($params as $key => $value) {
            $route = preg_replace('/\{' . preg_quote($key, '/') . '\}/', $value, $route);
        }
        return $route;
    }

    public function make()
    {
        switch ($this->type) {

            case 'rest':
                return $this->registerRest();

            case 'ajax':
                return $this->registerAjax();

            case 'admin':
                return $this->registerAdmin();

            case 'web':
                return $this->registerWeb();

            default:
                throw new \Exception("Unknown route group: {$this->type}");
        }
    }

    private function registerRest()
    {
        $dynamic = $this->generateDynamicRoute($this->route);

        add_action('rest_api_init', function () use ($dynamic) {
            $namespace ='';
            if($this->useNamespace){
                
                $namespace = appConfig('app.api.namespace', 'wplite/v1');
            }

            register_rest_route($namespace, "/{$dynamic}", [
                'methods' => $this->method,
                'callback' => function ($request) {
                    $args = $request->get_params();
                    return (new Pipeline())->call($request, [
                        'middlewares' => $this->middlewares,
                        'callable' => $this->callable,
                        'route' => $this->route,
                        'method' => $this->method,
                    ], $args);
                },
                'permission_callback' => '__return_true'
            ]);
        });
    }
    private function registerAjax()
    {
        $action = $this->route;

        $handler = function () {
            $request = $_REQUEST;

            return (new Pipeline())->call($request, [
                'middlewares' => $this->middlewares,
                'callable' => $this->callable,
                'route' => $this->route,
                'method' => $this->method,
            ], $request);
        };

        add_action("wp_ajax_{$action}", $handler);
        add_action("wp_ajax_nopriv_{$action}", $handler);
    }
    private function registerAdmin()
    {
        $slug = $this->route;

        add_action('admin_menu', function () use ($slug) {
            add_menu_page(
                $slug,
                ucfirst($slug),
                'manage_options',
                $slug,
                function () {
                    return (new Pipeline())->call($_REQUEST, [
                        'middlewares' => $this->middlewares,
                        'callable' => $this->callable,
                        'route' => $this->route,
                        'method' => $this->method,
                    ], $_REQUEST);
                }
            );
        });
    }

    private function registerWeb()
    {
        add_action('template_redirect', function () {

            $current = strtok($_SERVER["REQUEST_URI"], '?');

            if ($current === $this->route) {
                return (new Pipeline())->call($_REQUEST, [
                    'middlewares' => $this->middlewares,
                    'callable' => $this->callable,
                    'route' => $this->route,
                    'method' => $this->method,
                ], $_REQUEST);
            }
        });
    }
}
