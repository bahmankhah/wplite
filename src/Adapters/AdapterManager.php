<?php 

namespace WPLite\Adapters;

use WPLite\Facades\App;

abstract class AdapterManager{

    abstract public function getKey(): string;
    public function __call($method, $args){
        if(!method_exists($this, $method)){
            if (!appConfig("adapters.{$this->getKey()}.contexts.{$method}")) {
                $defaultAdapter = appConfig("adapters.{$this->getKey()}.default");
                $instance = $this->use($defaultAdapter);
                if(!method_exists($instance, $method)){
                    throw new \InvalidArgumentException("Message adapter [{$defaultAdapter}] does not have method [{$method}].");
                }
                return call_user_func_array([$instance, $method], $args);
            }
            return $this->use($method);
        }else{
            return call_user_func_array([$this, $method], $args);
        }
    }
    public function use(?string $adapter = null){
        
        if (!appConfig("adapters.{$this->getKey()}.contexts.{$adapter}")) {
            throw new \InvalidArgumentException("Message adapter [{$adapter}] is not defined.");
        }
        return App::make(appConfig("adapters.{$this->getKey()}.contexts.{$adapter}.context"),['config'=>appConfig("adapters.{$this->getKey()}.contexts.{$adapter}")]);
    }
}