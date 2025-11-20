<?php

namespace WPLite\Adapters;

class Adapter{
    protected $config;
    public function __construct(array $config) {
        $this->config = $config;
    }
    public function __call($method, $args){
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);
        }else{
            throw new \InvalidArgumentException("Method [{$method}] does not exist.");
        }
    }
}