<?php

namespace WPLite\Middlewares;

use WPLite\Contracts\Middleware;
use WPLite\Pipeline;
use WPLite\Facades\App;

/**
 * AppMiddleware — default global middleware that stores the request.
 *
 * Role: Captures the incoming request and binds it to the Application
 *       container so it can be accessed via App::request() downstream.
 *
 * This middleware is typically listed in configs/app.php 'api_middlewares'.
 *
 * @see \WPLite\Application::setRequest()  Where the request is stored.
 * @see \WPLite\Contracts\Middleware        Interface this implements.
 */
class AppMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        App::setRequest($request);
        return $pipeline->next($request);
    }
}
