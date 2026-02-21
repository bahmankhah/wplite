<?php

namespace WPLite\Contracts;

use WPLite\Pipeline;

/**
 * Middleware contract — defines the interface for request middleware.
 *
 * Role: Each middleware inspects/modifies the request and either passes
 *       it to the next middleware via $pipeline->next($request) or
 *       returns a response early to short-circuit the pipeline.
 *
 * How to use:
 *   - Implement handle($request, Pipeline $pipeline).
 *   - Attach to routes: ->middleware(MyMiddleware::class)
 *   - Or add globally in configs/app.php 'api_middlewares'.
 *
 * @see \WPLite\Pipeline  The pipeline that executes middleware.
 */
interface Middleware {
    public function handle($request, Pipeline $pipeline);
}