<?php

namespace WPLite\Adapters;

use WPLite\Facades\App;

/**
 * AdapterManager — abstract manager that resolves driver implementations.
 *
 * Role: Implements the Strategy pattern for swappable drivers. Subclasses
 *       define a config key, and the manager resolves the active driver
 *       from configs/adapters.php configuration.
 *
 * Responsibilities:
 *   - Define getKey() to identify which adapter config section to use.
 *   - Resolve named adapter contexts from config.
 *   - Instantiate driver classes via App::make() with config injection.
 *   - Proxy method calls to the default or named driver.
 *
 * How to use:
 *   - Extend this class and implement getKey():
 *     class CacheManager extends AdapterManager {
 *         public function getKey(): string { return 'cache'; }
 *     }
 *   - Configure drivers in configs/adapters.php under the matching key.
 *   - Access via facade: Cache::get('key') or Cache::use('redis')->get('key').
 *
 * Config structure (configs/adapters.php):
 *   'cache' => [
 *       'default' => 'transient',
 *       'contexts' => [
 *           'transient' => ['context' => Transient::class, ...config],
 *       ],
 *   ]
 *
 * @see \WPLite\Adapters\Adapter           Base driver class.
 * @see \WPLite\Cache\CacheManager          Concrete example.
 */
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