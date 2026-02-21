<?php

namespace WPLite;

use WPLite\Facades\App;

/**
 * Pipeline — middleware pipeline that chains request processing.
 *
 * Role: Executes an ordered sequence of middleware, then invokes the
 *       final controller method. Identical to Laravel's pipeline pattern.
 *
 * Responsibilities:
 *   - Merge global middleware (from config) with route-specific middleware.
 *   - Call each middleware's handle() in sequence.
 *   - Invoke the target controller method after all middleware pass.
 *
 * How to use:
 *   - This is called internally by RouteDefinition. You don't call it directly.
 *   - To add middleware, use ->middleware() on a route or add to
 *     configs/app.php 'api_middlewares'.
 *
 * Flow: Request → Middleware[0] → ... → Middleware[N] → Controller::method()
 * Each middleware calls $pipeline->next($request) to proceed.
 *
 * Avoid:
 *   - Do not instantiate Pipeline directly outside of route registration.
 *
 * @see \WPLite\Contracts\Middleware   The middleware interface.
 * @see \WPLite\RouteDefinition         Creates Pipeline instances.
 */
class Pipeline{
    private $middlewares = [];
    private $callIndex = 0;
    private $callable = [];   
    private $args;
    public function call($request, $params, $args = []){
        $this->args = $args;
        $this->middlewares = appConfig('app.api_middlewares', []);
        $this->middlewares = array_merge($this->middlewares, $params['middlewares']);
        $this->callable = $params['callable'];
        return $this->next($request);
    }

    public function next($request){
        if($this->callIndex === count($this->middlewares)){
            return (new $this->callable[0]())->{$this->callable[1]}($request, ...array_values($this->args));
            // $controller = App::make($this->callable[0]);
            // return $controller->{$this->callable[1]}($request);
        }else{
            return (new $this->middlewares[$this->callIndex++]())->handle($request, $this);
        }
    }
}