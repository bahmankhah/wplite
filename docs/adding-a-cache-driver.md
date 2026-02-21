# Adding a Cache Driver

WPLite uses the **Adapter pattern** for swappable cache backends. The framework
ships with a WordPress Transients driver. This guide shows how to add a new one.

---

## 1. Implement the CacheDriver Interface

Create a class that extends `Adapter` and implements `CacheDriver`:

```php
<?php
// src/Cache/Drivers/FileCache.php

namespace App\Cache\Drivers;

use WPLite\Adapters\Adapter;
use WPLite\Contracts\Cache\CacheDriver;
use WPLite\Facades\App;

class FileCache extends Adapter implements CacheDriver
{
    private function path(string $key): string
    {
        $dir = App::pluginPath() . 'cache/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . md5($key) . '.cache';
    }

    public function get(string $key)
    {
        $file = $this->path($key);
        if (!file_exists($file)) {
            return false;
        }

        $data = unserialize(file_get_contents($file));

        // Check expiration
        if ($data['expires'] > 0 && time() > $data['expires']) {
            $this->delete($key);
            return false;
        }

        return $data['value'];
    }

    public function set(string $key, $value, int $seconds = 0)
    {
        $data = [
            'value'   => $value,
            'expires' => $seconds > 0 ? time() + $seconds : 0,
        ];
        return file_put_contents($this->path($key), serialize($data)) !== false;
    }

    public function delete(string $key)
    {
        $file = $this->path($key);
        return file_exists($file) ? unlink($file) : true;
    }

    public function clear()
    {
        $dir = App::pluginPath() . 'cache/';
        array_map('unlink', glob($dir . '*.cache'));
    }
}
```

## 2. Register in Config

In `configs/adapters.php`:

```php
return [
    'cache' => [
        'default' => 'file',  // Switch the default driver
        'contexts' => [
            'transient' => [
                'context' => \WPLite\Cache\Drivers\Transient::class,
            ],
            'file' => [
                'context' => \App\Cache\Drivers\FileCache::class,
                // Any extra config is passed to the driver's $config:
                'ttl' => 3600,
            ],
        ],
    ],
];
```

## 3. Use It

```php
use WPLite\Facades\Cache;

// Uses the default driver (now 'file')
Cache::set('report', $data, 3600);
$cached = Cache::get('report');

// Use a specific driver
Cache::use('transient')->set('key', $value);
```

---

## Interface Reference

```php
interface CacheDriver
{
    public function get(string $key);
    public function set(string $key, $value, int $seconds = 0);
    public function delete(string $key);
    public function clear();
}
```

---

## Key Files

- **Contract**: `src/Contracts/Cache/CacheDriver.php`
- **Manager**: `src/Cache/CacheManager.php` (extends `AdapterManager`)
- **Built-in driver**: `src/Cache/Drivers/Transient.php`
- **Base adapter**: `src/Adapters/Adapter.php`
- **Adapter manager**: `src/Adapters/AdapterManager.php`
- **Facade**: `src/Facades/Cache.php`
- **Config**: `configs/adapters.php`
