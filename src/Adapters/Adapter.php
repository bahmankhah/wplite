<?php

namespace WPLite\Adapters;

/**
 * Adapter — base class for all adapter/driver implementations.
 *
 * Role: Holds the driver-specific configuration array and provides
 *       a common base for all pluggable implementations (cache drivers,
 *       auth guards, etc.).
 *
 * Responsibilities:
 *   - Accept and store a $config array from AdapterManager.
 *   - Provide a __call fallback for method dispatch.
 *
 * How to use:
 *   - Extend this class when creating a new driver (e.g., RedisCache, JwtGuard).
 *   - Access configuration via $this->config in your driver.
 *
 * @see \WPLite\Adapters\AdapterManager  Resolves and instantiates adapters.
 */
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