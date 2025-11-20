<?php

namespace WPLite\Middlewares;
use WPLite\Contracts\Middleware;
use WPLite\Pipeline;
use WPLite\Facades\App;
class AppMiddleware implements Middleware{
    public function handle($request,Pipeline $pipeline){
        App::setRequest($request);
        return $pipeline->next($request);
    }
}
