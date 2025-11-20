<?php

namespace WPLite;

use WPLite\Facades\App;

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