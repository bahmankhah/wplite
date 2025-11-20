<?php

namespace WPLite\Contracts;

use WPLite\Pipeline;

interface Middleware {
    public function handle($request, Pipeline $pipeline);
}