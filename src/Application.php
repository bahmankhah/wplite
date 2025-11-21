<?php

namespace WPLite;

use Exception;
use ReflectionClass;
use WPLite\Facades\App;

class Application extends Container{
    /**
     * Resolve a class instance from the container.
     *
     * @param string $class
     * @param array $params
     *
     * @return object
     *
     * @throws Exception
     */
    public function make($class, array $params = [])
    {
        if (!class_exists($class)) {
            throw new Exception("Class {$class} not found.");
        }

        $reflection = new ReflectionClass($class);

        // Get the constructor
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $class(); // No constructor, just instantiate
        }

        // Resolve the constructor's parameters
        $dependencies = $constructor->getParameters();
        $resolved = [];

        foreach ($dependencies as $dependency) {
            if (array_key_exists($dependency->getName(), $params)) {
                // Use provided parameter if available
                $resolved[] = $params[$dependency->getName()];
            } elseif ($dependency->getType() && !$dependency->getType()->isBuiltin()) {
                // Resolve non-built-in type dependencies
                $resolved[] = $this->resolve($dependency->getType()->getName());
            } elseif ($dependency->isDefaultValueAvailable()) {
                // Use the default value if available
                $resolved[] = $dependency->getDefaultValue();
            } else {
                throw new Exception("Unable to resolve dependency [{$dependency->getName()}] for class [{$class}].");
            }
        }
        
        return $reflection->newInstanceArgs($resolved);
    }

    public function setPluginPath($path) {
        self::bind('plugin_path', function() use ($path) {
            return $path;
        });
    }
    public function pluginPath(){
        return self::resolve('plugin_path');
    }
    public function setPluginFile($file) {
        self::bind('plugin_file', function() use ($file) {
            return $file;
        });
    }
    public function pluginFile(){
        return self::resolve('plugin_file');
    }

    public function setRequest($request){
        self::bind('request', function() use ($request) {
            return $request;
        });
    }

    public function request(){
        return self::resolve('request');
    }

    public function boot() {
        $this->startOutputBuffer();
        load_env_file( App::pluginPath() . '.env' );
        Config::load();
        ProviderManager::loadProviders();
    }
    private function startOutputBuffer()
    {
        if (!defined('WPLITE_OB_STARTED')) {
            ob_start();
            define('WPLITE_OB_STARTED', true);
        }
    }

}