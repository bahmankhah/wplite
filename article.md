---
title: "WPLite — A Laravel-Inspired Micro Framework for WordPress Plugin Development"
published: false
description: "Build modern, structured WordPress plugins using a lightweight framework with service containers, facades, middleware pipelines, Eloquent-style models, and more — all without the bloat."
tags: wordpress, php, laravel, opensource
cover_image: 
---

## The Problem with WordPress Plugin Development

If you've ever built a WordPress plugin beyond a simple shortcode, you know the pain: tangled procedural code, global state everywhere, callbacks registering callbacks, and zero separation of concerns. Scaling becomes a nightmare.

Meanwhile, frameworks like Laravel have taught the PHP community what clean architecture looks like — dependency injection, middleware pipelines, named routes, facades, service providers. Why can't we bring that to WordPress plugin development?

That's exactly what **WPLite** does.

## What Is WPLite?

[WPLite](https://github.com/bahmankhah/wplite) is a lightweight, Laravel-inspired micro framework for building WordPress plugins with a modern PHP architecture. It gives you all the structural building blocks — **service container**, **facades**, **middleware pipeline**, **routing**, **models with relationships**, **views**, **caching**, **auth guards** — without requiring you to leave the WordPress ecosystem.

It's not a replacement for WordPress. It's the scaffolding that lets you write WordPress plugins like a real application.

```bash
composer create-project hsm/wplite-plugin
```

---

## Key Features at a Glance

| Feature | Description |
|---|---|
| **Service Container** | Automatic dependency injection with constructor resolution |
| **Facades** | Static-like access to services (`App`, `Route`, `View`, `Cache`, `Auth`, `Wordpress`) |
| **4 Route Types** | REST API, Ajax, Admin pages, and Web (frontend) routes — all from one router |
| **Middleware Pipeline** | Chain middleware on any route, just like Laravel |
| **Eloquent-Style Model** | Query builder, relationships (`hasMany`, `hasOne`, `belongsTo`, `hasOneMeta`), `with()` |
| **Service Providers** | Lifecycle-aware hooks: `register`, `boot`, `onInit`, `activate`, `deactivate`, `uninstall` |
| **View Engine** | Simple PHP-based views with `view('path.to.view', $data)` |
| **Cache Layer** | Driver-based caching (ships with WordPress Transients) |
| **Auth Guards** | Pluggable authentication (ships with SSO/OAuth2 guard) |
| **Adapter Pattern** | Swap implementations via config (cache drivers, auth guards, etc.) |
| **JSON Resources** | Laravel-style API resource transformers |
| **Shortcode Base Class** | OOP shortcodes with attributes and defaults |
| **Namespace Isolation** | Build tool rewrites all namespaces — zero conflicts between plugins |
| **CLI Tool** | `php wplite build` to scaffold and brand the framework |
| **`.env` Support** | Environment variables loaded from `.env` file |
| **Config System** | Dot-notation config files (`appConfig('app.api.namespace')`) |
| **Logging** | Built-in file logger |

---

## Getting Started

### 1. Create a New Plugin Project

```bash
composer create-project hsm/wplite-plugin
```

### 2. Build the Framework with Your Namespace

This is the key step that makes WPLite unique among WordPress frameworks. The `build` command rewrites all framework namespaces to your plugin's namespace, so **two plugins using WPLite will never conflict**.

```bash
php vendor/hsm/wplite/wplite build --prefix=MyPlugin
```

This generates a `src/WPLite/` directory where every class is namespaced under `MyPlugin\WPLite\*`. The original package source is never modified.

### 3. Bootstrap Your Plugin

Your main plugin file looks clean and familiar:

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
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

That's it. Your plugin is running with a full service container, configuration system, environment variables, and automatic provider loading.

---

## Routing — Four Flavors, One Syntax

WPLite's router is its crown jewel. Define REST API routes, Ajax handlers, admin pages, and frontend (web) routes all with the same expressive API.

### REST API Routes (`routes/rest.php`)

```php
use MyPlugin\WPLite\Facades\Route;

Route::rest(function ($route) {
    $route->get('/users', [UserController::class, 'index']);
    $route->get('/users/{id}', [UserController::class, 'show']);
    $route->post('/users', [UserController::class, 'store'])
          ->middleware(AuthMiddleware::class);
});
```

This registers routes under the WordPress REST API (default namespace from your `configs/app.php`). Dynamic route parameters like `{id}` are supported out of the box.

### Ajax Routes (`routes/ajax.php`)

```php
Route::ajax(function ($route) {
    $route->post('submit_form', [FormController::class, 'handle'])
          ->middleware(NonceMiddleware::class);
});
```

Automatically hooks into `wp_ajax_` and `wp_ajax_nopriv_`.

### Admin Pages (`routes/admin.php`)

```php
Route::admin(function ($route) {
    $route->get('my-plugin-settings', [SettingsController::class, 'index']);
});
```

Creates a WordPress admin menu page — no `add_menu_page` boilerplate.

### Web Routes (`routes/web.php`)

```php
Route::web(function ($route) {
    $route->get('/custom-page', [PageController::class, 'show']);
});
```

Intercepts frontend requests via `template_redirect`. Perfect for custom landing pages.

### Named Routes

```php
$route->get('/invoices/{id}', [InvoiceController::class, 'show'])
      ->name('invoice.show');

// Later, generate the URL:
$url = reverse('invoice.show', ['id' => 42]);
```

---

## Middleware Pipeline

Every route passes through a middleware pipeline. Global middleware is defined in your config, and per-route middleware is chained fluently:

```php
$route->post('/checkout', [CheckoutController::class, 'process'])
      ->middleware(AuthMiddleware::class, LoggingMiddleware::class);
```

Writing middleware is straightforward:

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

The pipeline resolves each middleware in sequence, calling `$pipeline->next($request)` to proceed — identical to how Laravel handles it.

---

## Service Container & Dependency Injection

The container automatically resolves constructor dependencies:

```php
use MyPlugin\WPLite\Facades\App;

$service = App::make(OrderService::class);
```

If `OrderService` depends on a `PaymentGateway`, and `PaymentGateway` depends on `Config`, the container resolves the entire chain automatically via reflection. You can also pass explicit parameters:

```php
$service = App::make(ReportService::class, ['year' => 2026]);
```

Bind services manually:

```php
App::bind('mailer', function () {
    return new SmtpMailer(appConfig('mail.host'));
});

$mailer = App::resolve('mailer');
```

---

## Service Providers — Lifecycle-Aware Hooks

Service providers are the backbone of plugin organization. Extend the `Provider` class and hook into the WordPress lifecycle with clear, semantic methods:

```php
use MyPlugin\WPLite\Provider;

class PaymentProvider extends Provider
{
    public function register()
    {
        // Bind services to the container
    }

    public function bootEarly()
    {
        // Runs before WordPress `init`
    }

    public function onInit()
    {
        // Runs on the `init` hook
    }

    public function boot()
    {
        // Runs after WordPress is fully loaded (`wp_loaded`)
    }

    public function admin()
    {
        // Admin-only logic
    }

    public function ajax()
    {
        // Ajax-only logic
    }

    public function rest()
    {
        // REST API initialization
    }

    public function activate()
    {
        // Plugin activation (create tables, set options)
    }

    public function deactivate()
    {
        // Plugin deactivation cleanup
    }

    public function uninstall()
    {
        // Plugin uninstall (delete data)
    }
}
```

Register providers in `configs/app.php` or place them under `Src\Provider\` — they're auto-discovered.

---

## Models & Query Builder

WPLite ships with a query builder that wraps `$wpdb` with a fluent API:

```php
use MyPlugin\WPLite\Model;

class Order extends Model
{
    protected $table = 'wp_orders';
    protected $primaryKey = 'id';

    public function items()
    {
        return $this->hasMany('wp_order_items', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo('wp_customers', 'customer_id');
    }

    public function status_meta()
    {
        return $this->hasOneMeta('wp_ordermeta', 'status', 'id', 'order_id');
    }
}
```

Query it expressively:

```php
$order = new Order();

$results = $order
    ->select(['id', 'total', 'created_at'])
    ->where('status', '=', 'completed')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->items()   // eager-load hasMany relationship
    ->customer() // eager-load belongsTo relationship
    ->get();
```

Full support for:
- `select`, `where`, `join`, `orderBy`, `groupBy`, `limit`
- `hasMany`, `hasOne`, `belongsTo`, `hasOneMeta` relationships
- `with()` for custom eager-loaded computed fields
- `hide()` to strip fields from results
- `create`, `update`, `delete` operations
- Raw SQL access via `->sql()`

---

## Facades

Clean, static-like access to underlying services:

```php
use MyPlugin\WPLite\Facades\App;
use MyPlugin\WPLite\Facades\Route;
use MyPlugin\WPLite\Facades\View;
use MyPlugin\WPLite\Facades\Cache;
use MyPlugin\WPLite\Facades\Auth;
use MyPlugin\WPLite\Facades\Config;
use MyPlugin\WPLite\Facades\Wordpress;

// Views
View::render('dashboard.index', ['stats' => $stats]);
// or use the helper:
view('dashboard.index', ['stats' => $stats]);

// Cache (uses WordPress Transients by default)
Cache::set('report_data', $data, 3600);
$cached = Cache::get('report_data');

// WordPress hooks via facade
Wordpress::action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
Wordpress::filter('the_content', [$this, 'filterContent']);
Wordpress::shortcode('my_shortcode', [$this, 'renderShortcode']);
```

---

## Cache System

The cache layer uses the **Adapter pattern** — swap drivers without changing application code:

```php
// Default driver (Transients)
Cache::set('key', 'value', 3600);
Cache::get('key');
Cache::delete('key');
Cache::clear();
```

Built-in driver: **WordPress Transients**. Add your own (Redis, file-based, etc.) by implementing `CacheDriver` and configuring it in `configs/adapters.php`.

---

## Auth Guards

Pluggable authentication with a contract-driven design:

```php
Auth::check();           // Is the user authenticated?
Auth::user();            // Get the current user
Auth::login($user);      // Log in
Auth::logout();          // Log out
Auth::attempt($creds);   // Attempt authentication
```

Ships with an **SSO/OAuth2 guard** out of the box. Create your own guard by implementing the `Guard` contract.

---

## JSON Resources

Transform your data for API responses:

```php
use MyPlugin\WPLite\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray()
    {
        return [
            'id' => $this->data['id'],
            'total' => number_format($this->data['total'], 2),
            'status' => $this->data['status'],
        ];
    }
}

// Single item
$resource = OrderResource::make($order);
return $resource->toArray();

// Collection
$collection = OrderResource::collection($orders);
```

---

## OOP Shortcodes

Define shortcodes as classes:

```php
use MyPlugin\WPLite\Shortcode;

class PricingTable extends Shortcode
{
    protected $tag = 'pricing_table';

    protected function defaults(): array
    {
        return ['plan' => 'basic', 'columns' => 3];
    }

    public function render()
    {
        return view('shortcodes.pricing', [
            'plan' => $this->attributes['plan'],
            'columns' => $this->attributes['columns'],
        ]);
    }
}

// Register in a provider:
PricingTable::register();
```

Usage in WordPress: `[pricing_table plan="pro" columns="4"]`

---

## Views

Simple, clean PHP templating:

```php
// Renders views/emails/welcome.view.php
view('emails.welcome', ['name' => 'John']);
```

Inside your view file:

```php
<h1>Welcome, <?= $name ?>!</h1>
```

Dot notation maps to directory structure. Data is extracted into variables automatically.

---

## Configuration & Environment

### Config Files

Place PHP config files in `configs/`:

```php
// configs/app.php
return [
    'name' => 'my-plugin',
    'api' => [
        'namespace' => 'myplugin/v1',
    ],
    'providers' => [
        PaymentProvider::class,
    ],
    'api_middlewares' => [
        AppMiddleware::class,
    ],
];
```

Access with dot notation:

```php
$namespace = appConfig('app.api.namespace');
$name = appConfig('app.name', 'default-value');
```

### Environment Variables

Create a `.env` file in your plugin root:

```env
API_KEY=sk_live_abc123
DEBUG_MODE=true
SSO_CLIENT_ID=my-client
```

Access via standard PHP:

```php
$key = getenv('API_KEY');
```

---

## Namespace Isolation — The Killer Feature

Here's the problem with most WordPress frameworks: if two plugins use the same framework, class names collide. WPLite solves this with a **build-time namespace rewriting tool**.

```bash
php vendor/hsm/wplite/wplite build --prefix=MyPlugin
```

What this does:

1. Copies the framework source to `src/WPLite/` in your project
2. Rewrites every `namespace WPLite\*` → `namespace MyPlugin\WPLite\*`
3. Rewrites every `use WPLite\*` → `use MyPlugin\WPLite\*`
4. Updates all fully qualified class references and PHPDoc annotations
5. Namespaces helper functions to prevent global collisions

The result: **two plugins using WPLite coexist without any conflict** — even on the same WordPress installation. The configuration is saved to `wplite-config.json` so subsequent builds only need:

```bash
php vendor/hsm/wplite/wplite build
```

A `--dry-run` flag lets you preview all changes before committing.

---

## Project Structure

After setup, your plugin follows a clean, organized structure:

```
my-plugin/
├── my-plugin.php          # Main plugin file (bootstrap)
├── composer.json
├── wplite-config.json     # Build configuration
├── .env                   # Environment variables
├── configs/
│   ├── app.php            # App config (providers, middleware, etc.)
│   └── adapters.php       # Adapter configs (cache, auth, etc.)
├── routes/
│   ├── rest.php           # REST API routes
│   ├── ajax.php           # Ajax routes
│   ├── admin.php          # Admin page routes
│   └── web.php            # Frontend web routes
├── views/
│   └── dashboard/
│       └── index.view.php
├── src/
│   ├── WPLite/            # Built framework (gitignored)
│   ├── Controllers/
│   ├── Models/
│   ├── Middlewares/
│   └── Provider/          # Auto-discovered providers
└── vendor/
```

---

## CLI Reference

```bash
# Build framework with namespace prefix
php vendor/hsm/wplite/wplite build --prefix=MyPlugin

# Use saved prefix from wplite-config.json
php vendor/hsm/wplite/wplite build

# Preview changes without writing files
php vendor/hsm/wplite/wplite build --dry-run

# Custom output directory
php vendor/hsm/wplite/wplite build --prefix=MyPlugin --output=lib/Core

# Show help
php vendor/hsm/wplite/wplite --help
```

---

## Why WPLite?

| | Traditional WP Plugin | WPLite Plugin |
|---|---|---|
| **Architecture** | Procedural spaghetti | MVC + Service Providers |
| **Routing** | Manual `add_action` hooks | Expressive route definitions |
| **Dependencies** | Global state, `new` everywhere | Auto-resolving container |
| **Middleware** | Manual permission callbacks | Chainable pipeline |
| **Database** | Raw `$wpdb` calls | Fluent query builder + relationships |
| **Testing** | Difficult | Isolated, injectable services |
| **Multi-plugin safety** | Class name collisions | Namespace isolation per plugin |

---

## Requirements

- PHP >= 7.4
- WordPress (any modern version)
- Composer

---

## Get Started

```bash
composer create-project hsm/wplite-plugin
cd my-plugin
php vendor/hsm/wplite/wplite build --prefix=MyPlugin
```

GitHub: [https://github.com/bahmankhah/wplite](https://github.com/bahmankhah/wplite)

License: MIT

---

*WPLite doesn't try to reinvent WordPress. It gives you the structural discipline of modern PHP frameworks while staying 100% inside the WordPress ecosystem. If you've ever thought "I wish WordPress plugin development felt more like Laravel" — this is for you.*

If you found this useful, drop a star on [the repo](https://github.com/bahmankhah/wplite) and share it with a fellow WordPress developer!
