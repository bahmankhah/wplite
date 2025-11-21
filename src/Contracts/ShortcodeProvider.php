<?php

namespace WPLite\Contracts;

interface ShortcodeProvider {
    public function render();
    protected function defaults(): array;
    public static function register();
}