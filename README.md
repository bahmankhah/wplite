# WPLite

A lightweight, Laravel-inspired micro framework for building WordPress plugins with modern PHP architecture.

WPLite gives you **service containers**, **facades**, **middleware pipelines**, **expressive routing**, **Eloquent-style models**, **views**, **caching**, **auth guards**, and more — without leaving the WordPress ecosystem.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF.svg)](https://php.net)

---

## Quick Start

```bash
composer create-project hsm/wplite-plugin
cd my-plugin
php vendor/hsm/wplite/wplite build --prefix=MyPlugin
```

---

## Features

- **Service Container** — Auto-resolving dependency injection via reflection
- **Facades** — Static-like access: `App`, `Route`, `View`, `Cache`, `Auth`, `Config`, `Wordpress`
- **4 Route Types** — REST API, Ajax, Admin pages, and Web routes from one unified router
- **Middleware Pipeline** — Chainable middleware on any route (Laravel-style `$pipeline->next()`)
- **Query Builder & Models** — Fluent `$wpdb` wrapper with `hasMany`, `hasOne`, `belongsTo`, `hasOneMeta` relationships
- **Service Providers** — Lifecycle hooks: `register`, `bootEarly`, `onInit`, `boot`, `admin`, `ajax`, `rest`, `activate`, `deactivate`, `uninstall`
- **View Engine** — PHP-based templating with dot notation (`view('emails.welcome', $data)`)
- **Cache Layer** — Driver-based (ships with WordPress Transients), extensible via Adapter pattern
- **Auth Guards** — Pluggable authentication (ships with SSO/OAuth2 guard)
- **JSON Resources** — API response transformers (`::make()`, `::collection()`)
- **OOP Shortcodes** — Class-based shortcodes with attributes and defaults
- **Namespace Isolation** — Build tool rewrites all namespaces so multiple WPLite plugins never conflict
- **CLI Tool** — `php wplite build` to scaffold and brand the framework
- **`.env` Support** — Environment variables loaded automatically
- **Config System** — Dot-notation access (`appConfig('app.api.namespace')`)
- **Logging** — Built-in file logger

---

## Requirements

- PHP >= 7.4
- WordPress (any modern version)
- Composer

---

## Installation

### New Project

```bash
composer create-project hsm/wplite-plugin
```

### Add to Existing Plugin

```bash
composer require hsm/wplite
```

---

## Namespace Isolation (Build Step)

**This is the critical step.** WPLite rewrites all framework namespaces to your plugin's unique namespace so that **multiple plugins using WPLite coexist on the same WordPress installation without any conflict**.

```bash
php vendor/hsm/wplite/wplite build --prefix=MyPlugin
```

What this does:

1. Copies the framework source into `src/WPLite/` in your project
2. Rewrites every `namespace WPLite\*` → `namespace MyPlugin\WPLite\*`
3. Rewrites every `use WPLite\*` → `use MyPlugin\WPLite\*`
4. Updates all fully qualified class references and PHPDoc annotations
5. Namespaces helper functions to prevent global collisions
6. Saves your prefix to `wplite-config.json` for future builds

After the first run, subsequent builds only need:

```bash
php vendor/hsm/wplite/wplite build
```

Use `--dry-run` to preview all changes without writing files.

### CLI Options

```bash
php vendor/hsm/wplite/wplite build --prefix=MyPlugin        # Build with prefix
php vendor/hsm/wplite/wplite build                           # Use saved prefix
php vendor/hsm/wplite/wplite build --dry-run                 # Preview changes
php vendor/hsm/wplite/wplite build --output=lib/Core         # Custom output dir
php vendor/hsm/wplite/wplite --help                          # Show help
```

---

## Bootstrap

Your main plugin file:

```php
<?php
/**
 * Plugin Name: My Plugin
 * Description: Built with WPLite.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/WPLite/helpers.php';

use MyPlugin\WPLite\Facades\App;

App::setPluginFile(__FILE__);
App::setPluginPath(plugin_dir_path(__FILE__));
App::boot();
```

---

## Routing

Define routes in `routes/rest.php`, `routes/ajax.php`, `routes/admin.php`, and `routes/web.php`.

### REST API

```php
Route::rest(function ($route) {
    $route->get('/users/{id}', [UserController::class, 'show'])
          ->middleware(AuthMiddleware::class)
          ->name('user.show');
});
```

### Ajax

```php
Route::ajax(function ($route) {
    $route->post('submit_form', [FormController::class, 'handle']);
});
```

Automatically registers `wp_ajax_` and `wp_ajax_nopriv_` hooks.

### Admin Pages

```php
Route::admin(function ($route) {
    $route->get('my-settings', [SettingsController::class, 'index']);
});
```

### Web (Frontend)

```php
Route::web(function ($route) {
    $route->get('/custom-page', [PageController::class, 'show']);
});
```

### Named Routes & URL Generation

```php
$url = reverse('user.show', ['id' => 42]);
```

---

## Middleware

```php
use MyPlugin\WPLite\Contracts\Middleware;
use MyPlugin\WPLite\Pipeline;

class AuthMiddleware implements Middleware
{
    public function handle($request, Pipeline $pipeline)
    {
        if (!is_user_logged_in()) {
            return wp_send_json_error('Unauthorized', 401);
        }
        return $pipeline->next($request);
    }
}
```

Chain per-route or define global middleware in `configs/app.php`:

```php
$route->post('/checkout', [CheckoutController::class, 'process'])
      ->middleware(AuthMiddleware::class, LogMiddleware::class);
```

---

## Service Container

```php
use MyPlugin\WPLite\Facades\App;

// Auto-resolve with dependency injection
$service = App::make(OrderService::class);

// Manual bindings
App::bind('mailer', fn() => new SmtpMailer(appConfig('mail.host')));
$mailer = App::resolve('mailer');
```

---

## Service Providers

```php
use MyPlugin\WPLite\Provider;

class PaymentProvider extends Provider
{
    public function register()    { /* bind services */ }
    public function bootEarly()   { /* before init */ }
    public function onInit()      { /* on init hook */ }
    public function boot()        { /* after wp_loaded */ }
    public function admin()       { /* admin only */ }
    public function ajax()        { /* ajax only */ }
    public function rest()        { /* rest api init */ }
    public function activate()    { /* plugin activation */ }
    public function deactivate()  { /* plugin deactivation */ }
    public function uninstall()   { /* plugin uninstall */ }
}
```

Register in `configs/app.php` or place under `Src\Provider\` for auto-discovery.

---

## Models & Query Builder

```php
use MyPlugin\WPLite\Model;

class Order extends Model
{
    protected $table = 'wp_orders';

    public function items()
    {
        return $this->hasMany('wp_order_items', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo('wp_customers', 'customer_id');
    }
}
```

```php
$orders = (new Order())
    ->select(['id', 'total'])
    ->where('status', '=', 'completed')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->items()
    ->customer()
    ->get();
```

Supports: `select`, `where`, `join`, `orderBy`, `groupBy`, `limit`, `hasMany`, `hasOne`, `belongsTo`, `hasOneMeta`, `with()`, `hide()`, `create`, `update`, `delete`.

---

## Facades

```php
View::render('dashboard.index', ['stats' => $stats]);
Cache::set('key', $value, 3600);
Cache::get('key');
Auth::check();
Auth::login($user);
Wordpress::action('wp_enqueue_scripts', [$this, 'assets']);
Wordpress::filter('the_content', [$this, 'filter']);
```

---

## Views

```php
// Renders views/emails/welcome.view.php
view('emails.welcome', ['name' => 'John']);
```

```html
<!-- views/emails/welcome.view.php -->
<h1>Welcome, <?= $name ?>!</h1>
```

---

## JSON Resources

```php
use MyPlugin\WPLite\JsonResource;

class UserResource extends JsonResource
{
    public function toArray()
    {
        return [
            'id'   => $this->data['id'],
            'name' => $this->data['display_name'],
        ];
    }
}

$single = UserResource::make($user)->toArray();
$list = UserResource::collection($users);
```

---

## Shortcodes

```php
use MyPlugin\WPLite\Shortcode;

class PricingTable extends Shortcode
{
    protected $tag = 'pricing_table';

    protected function defaults(): array
    {
        return ['plan' => 'basic'];
    }

    public function render()
    {
        return view('shortcodes.pricing', [
            'plan' => $this->attributes['plan'],
        ]);
    }
}

// Register in a provider
PricingTable::register();
```

Usage: `[pricing_table plan="pro"]`

---

## Cache

```php
Cache::set('report', $data, 3600);
Cache::get('report');
Cache::delete('report');
Cache::clear();
```

Ships with **WordPress Transients** driver. Extend by implementing the `CacheDriver` contract.

---

## Configuration

### Config Files (`configs/app.php`)

```php
return [
    'name' => 'my-plugin',
    'api' => ['namespace' => 'myplugin/v1'],
    'providers' => [PaymentProvider::class],
    'api_middlewares' => [AppMiddleware::class],
];
```

```php
$value = appConfig('app.api.namespace');
$name = appConfig('app.name', 'default');
```

### Environment Variables (`.env`)

```env
API_KEY=sk_live_abc123
DEBUG_MODE=true
```

```php
$key = getenv('API_KEY');
```

---

## Project Structure

```
my-plugin/
├── my-plugin.php              # Main plugin file
├── composer.json
├── wplite-config.json         # Build config (prefix)
├── .env
├── configs/
│   ├── app.php
│   └── adapters.php
├── routes/
│   ├── rest.php
│   ├── ajax.php
│   ├── admin.php
│   └── web.php
├── views/
│   └── *.view.php
├── src/
│   ├── WPLite/                # Built framework (gitignored)
│   ├── Controllers/
│   ├── Models/
│   ├── Middlewares/
│   └── Provider/              # Auto-discovered providers
└── vendor/
```

---

## Contributing

Pull requests and issues are welcome! Please follow PSR standards.

## License

MIT — see [LICENSE](LICENSE) for details.
