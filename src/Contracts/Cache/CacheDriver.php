<?php
namespace WPLite\Contracts\Cache;

interface CacheDriver
{
    public function get(string $key);
    public function set(string $key, $value, int $seconds = 0);
    public function delete(string $key);
    public function clear();
}