# AGENTS.md — WPLite Framework Guide for AI Agents & Developers

> **Purpose**: This document enables AI coding agents and human developers to quickly
> understand the WPLite framework, locate code, and extend it correctly.

---

## What Is WPLite?

WPLite is a **Laravel-inspired micro-framework for building WordPress plugins** with
modern PHP architecture. It provides:

- A **service container** with auto-resolving dependency injection
- **Facades** for static-like access to services
- A **unified router** for REST API, Ajax, Admin pages, and Web routes
- A **middleware pipeline** (identical to Laravel's `$pipeline->next()` pattern)
- **Service providers** with lifecycle hooks mapped to WordPress actions
- An **Eloquent-style query builder** wrapping `$wpdb`
- A **view engine**, **cache layer**, **auth guards**, and **JSON resources**
- A **build tool** that rewrites namespaces so multiple WPLite plugins coexist

---

## Folder Structure

```
wplite/
├── AGENTS.md                  # ← You are here
├── README.md                  # User-facing documentation
├── article.md                 # Blog-style writeup of WPLite
├── composer.json              # Composer package definition (hsm/wplite)
├── main-sample.php            # Sample WordPress plugin bootstrap file
├── wplite                     # CLI entry point (bash → CommandRunner)
├── wplite-config.json         # Build config (stores namespace prefix)
│
├── cli/                       # CLI build tool (not loaded at runtime)
│   ├── CommandRunner.php      #   CLI dispatcher; parses argv, runs commands
│   └── Commands/
│       ├── Command.php        #   Abstract CLI command base class
│       ├── BuildCommand.php   #   `wplite build` — namespace isolation tool
│       └── InstallCommand.php #   Deprecated; destructive in-place branding
│
├── src/                       # Framework source code (WPLite\ namespace)
│   ├── autoload.php           #   PSR-4 autoloader + helper bootstrap (standalone use)
│   ├── Application.php        #   Main app class; extends Container with DI + boot()
│   ├── Container.php          #   Static service container (bind/resolve/has)
│   ├── Config.php             #   Loads configs/*.php files; dot-notation access
│   ├── Provider.php           #   Abstract service provider base class
│   ├── ProviderManager.php    #   Discovers, instantiates, and wires providers into WP hooks
│   ├── RouteManager.php       #   Router: creates RouteDefinitions in typed groups
│   ├── RouteDefinition.php    #   Single route: registers into WP (rest/ajax/admin/web)
│   ├── Pipeline.php           #   Middleware pipeline; chains middleware → controller
│   ├── Model.php              #   Fluent query builder wrapping $wpdb + relationships
│   ├── DB.php                 #   Static database helper (raw queries, WP_Query)
│   ├── JsonResource.php       #   Abstract API response transformer (make/collection)
│   ├── ViewManager.php        #   PHP-based view engine with dot-notation paths
│   ├── WordpressManager.php   #   Thin wrapper for add_action/add_filter/add_shortcode
│   ├── Shortcode.php          #   Abstract OOP shortcode base class
│   ├── PostType.php           #   Example custom post type (concrete, not abstract)
│   │
│   ├── Contracts/             #   Interfaces (contracts)
│   │   ├── ServiceProvider.php    # Lifecycle interface for providers
│   │   ├── Middleware.php         # handle($request, Pipeline) contract
│   │   ├── Auth/
│   │   │   ├── Guard.php          # Auth guard contract (check/user/login/logout/attempt)
│   │   │   └── Authenticatable.php# User model contract
│   │   └── Cache/
│   │       └── CacheDriver.php    # Cache driver contract (get/set/delete/clear)
│   │
│   ├── Facades/               #   Static-like accessors for container services
│   │   ├── Facade.php         #   Abstract facade base (resolves via getFacadeAccessor)
│   │   ├── App.php            #   → Application
│   │   ├── Auth.php           #   → Auth\AuthManager (via adapter pattern)
│   │   ├── Cache.php          #   → Cache\CacheManager (via adapter pattern)
│   │   ├── Config.php         #   → Config
│   │   ├── Route.php          #   → RouteManager
│   │   ├── View.php           #   → ViewManager
│   │   └── Wordpress.php      #   → WordpressManager
│   │
│   ├── Helpers/
│   │   └── main.php           #   Global helper functions (appConfig, view, reverse, etc.)
│   │
│   ├── Middlewares/
│   │   └── AppMiddleware.php  #   Default middleware: stores request on App
│   │
│   ├── Providers/
│   │   └── RouteServiceProvider.php # Loads routes/{rest,ajax,admin,web}.php on init
│   │
│   ├── Adapters/              #   Strategy/driver pattern
│   │   ├── Adapter.php        #   Base adapter (holds $config)
│   │   └── AdapterManager.php #   Abstract manager: resolves drivers from config
│   │
│   ├── Auth/
│   │   ├── GenericUser.php    #   Example Authenticatable implementation
│   │   └── Guards/
│   │       └── SSOGuard.php   #   SSO/OAuth2 guard implementation
│   │
│   └── Cache/
│       ├── CacheManager.php   #   Cache adapter manager (key: 'cache')
│       └── Drivers/
│           └── Transient.php  #   WordPress Transients cache driver
│
├── docs/                      #   Task-oriented guides (see below)
│
└── vendor/                    #  ⛔ Composer-managed; never edit
```

---

## Core Concepts

### 1. Container (`Container.php` → `Application.php`)

The **service container** is the foundation. It stores and resolves services.

| Method | Description |
|---|---|
| `Container::bind($name, $resolver)` | Register a factory closure |
| `Container::resolve($name)` | Retrieve (calls the factory) |
| `Container::has($name)` | Check if bound |
| `Application::make($class, $params)` | Auto-resolve class via reflection DI |
| `Application::boot()` | Load .env → Config → Providers |

Access via the `App` facade: `App::make(MyService::class)`.

### 2. Provider (≈ "Module")

A **Provider** is the primary organizational unit — equivalent to a "module".
It implements lifecycle hooks that map to WordPress action timing:

| Hook | When it runs | Use for |
|---|---|---|
| `register()` | Immediately | Bind services to container |
| `bootEarly()` | Immediately (after register) | Pre-init setup |
| `onInit()` | `init` action | Register post types, taxonomies |
| `boot()` | `wp_loaded` action | Feature logic after WP is ready |
| `admin()` | Always (check `is_admin()` yourself) | Admin-only bindings |
| `ajax()` | When `wp_doing_ajax()` is true | Ajax handlers |
| `rest()` | `rest_api_init` action | Register REST routes |
| `activate()` | Plugin activation hook | Create tables, set defaults |
| `deactivate()` | Plugin deactivation hook | Cleanup |
| `uninstall()` | Plugin uninstall hook | Delete all data |

**Registration**: Add the class to `configs/app.php` `providers` array, or place it
under the `Src\Provider\` namespace for auto-discovery.

### 3. Routing (≈ "Endpoint")

Routes are defined in `routes/{rest,ajax,admin,web}.php` using the `Route` facade:

```php
Route::rest(function ($route) {
    $route->get('/users/{id}', [UserController::class, 'show'])
          ->middleware(AuthMiddleware::class)
          ->name('user.show');
});
```

Four route types exist, each registering into a different WordPress hook:

| Type | WordPress mechanism | Route file |
|---|---|---|
| `rest` | `register_rest_route()` | `routes/rest.php` |
| `ajax` | `wp_ajax_` / `wp_ajax_nopriv_` | `routes/ajax.php` |
| `admin` | `add_menu_page()` | `routes/admin.php` |
| `web` | `template_redirect` URL matching | `routes/web.php` |

Each route passes through the **middleware pipeline** before reaching the controller.

### 4. Middleware Pipeline

The `Pipeline` class chains middleware in order, then calls the final controller:

```
Request → GlobalMiddleware[] → RouteMiddleware[] → Controller::method()
```

Each middleware implements `Middleware::handle($request, Pipeline $pipeline)` and
must call `$pipeline->next($request)` to continue, or return early to abort.

Global middleware is configured in `configs/app.php` under `api_middlewares`.

### 5. Model & Query Builder

`Model` wraps `$wpdb` with a fluent API:

```php
(new Order())->where('status', '=', 'active')->orderBy('id', 'DESC')->limit(10)->get();
```

Relationships: `hasMany`, `hasOne`, `belongsTo`, `hasOneMeta`, `with()`.

### 6. Adapter Pattern (Cache, Auth, etc.)

`AdapterManager` resolves driver implementations from `configs/adapters.php`:

```php
// configs/adapters.php
return [
    'cache' => [
        'default' => 'transient',
        'contexts' => [
            'transient' => [
                'context' => \WPLite\Cache\Drivers\Transient::class,
            ],
        ],
    ],
];
```

`CacheManager` and `AuthManager` extend `AdapterManager`. The `Adapter` base class
holds `$config` and is extended by each concrete driver.

### 7. Facades

Facades provide `static`-like access. Under the hood, each facade:
1. Calls `getFacadeAccessor()` to get a class name
2. Resolves (or instantiates) that class from the container
3. Proxies the static method call to the instance

Available facades: `App`, `Auth`, `Cache`, `Config`, `Route`, `View`, `Wordpress`.

### 8. Views

`ViewManager::render('path.to.view', $data)` renders `views/path/to/view.view.php`.
Dot notation maps to directory separators. Data is `extract()`-ed into scope.

Helper: `view('emails.welcome', ['name' => $name])`.

### 9. Jobs / Background Tasks

> **Not yet implemented.** This is a natural extension point. You would create a
> `Job` base class, a `Queue` system wrapping `wp_schedule_single_event()` or
> `wp_cron`, and register jobs in providers.

---

## Conventions & Patterns

### Naming Conventions

| Concept | Convention | Example |
|---|---|---|
| Provider | `{Feature}Provider` | `PaymentProvider` |
| Controller | `{Resource}Controller` | `UserController` |
| Middleware | `{Purpose}Middleware` | `AuthMiddleware` |
| Model | Singular noun | `Order`, `Invoice` |
| JsonResource | `{Model}Resource` | `UserResource` |
| Shortcode | `{Name}Shortcode` or descriptive | `PricingTable` |
| Cache driver | Descriptive class in `Cache/Drivers/` | `Transient`, `Redis` |
| Auth guard | `{Strategy}Guard` in `Auth/Guards/` | `SSOGuard`, `JwtGuard` |
| Route files | `routes/{type}.php` | `routes/rest.php` |
| Config files | `configs/{name}.php` | `configs/app.php` |
| View files | `views/{path}.view.php` | `views/emails/welcome.view.php` |

### Where to Add New Code (in a consumer plugin)

| What | Where |
|---|---|
| New feature module | Create a Provider in `src/Provider/` (auto-discovered) or register in `configs/app.php` |
| New REST endpoint | Add route in `routes/rest.php` + create Controller class |
| New Ajax handler | Add route in `routes/ajax.php` |
| New admin page | Add route in `routes/admin.php` |
| New middleware | Create class implementing `Middleware` contract, attach via `->middleware()` or global config |
| New model | Create class extending `Model`, set `$table` |
| New cache driver | Implement `CacheDriver`, extend `Adapter`, register in `configs/adapters.php` |
| New auth guard | Implement `Guard`, extend `Adapter`, register in `configs/adapters.php` |
| New shortcode | Extend `Shortcode`, call `::register()` in a provider |
| New view | Create `views/{path}.view.php` |
| New config | Create `configs/{name}.php` returning an array |
| New helper function | Add to plugin's own helpers file (not WPLite's) |

---

## Do / Don't

### DO

- Extend `Provider` for new feature modules — use the lifecycle hooks
- Use `App::make()` for dependency injection instead of `new`
- Define routes in `routes/*.php` files; use middleware for auth/validation
- Use the `Middleware` contract for cross-cutting concerns
- Extend `Model` for database tables; use the fluent query builder
- Use `appConfig()` for configuration; keep secrets in `.env`
- Run `php wplite build --prefix=YourPrefix` to namespace-isolate the framework
- Put consumer code under `src/Controllers/`, `src/Models/`, `src/Provider/`, etc.

### DON'T

- **Never edit `vendor/`** — it's Composer-managed
- **Never edit the built `src/WPLite/` directory** in a consumer plugin — it's
  regenerated by `wplite build`
- Don't modify framework source files (`wplite/src/`) in a consumer project;
  instead extend classes or use the adapter pattern
- Don't bypass the pipeline for route handlers — always go through `Route`
- Don't store secrets in `configs/*.php` — use `.env` files
- Don't call `Container::bind/resolve` directly if a Facade exists — use the facade
- Don't register WordPress hooks manually when a Provider lifecycle hook exists

---

## Boot Sequence

```
1. main-plugin.php
   ├── require vendor/autoload.php
   ├── App::setPluginFile(__FILE__)
   ├── App::setPluginPath(plugin_dir_path(__FILE__))
   └── App::boot()
        ├── Start output buffer
        ├── Load .env file
        ├── Config::load()  →  reads configs/*.php into $GLOBALS
        └── ProviderManager::loadProviders()
             ├── Merge built-in + config + auto-discovered providers
             ├── For each provider:
             │    ├── new Provider()
             │    ├── ->register()
             │    └── ->bootEarly()
             ├── add_action('init')       → ->onInit()
             ├── add_action('wp_loaded')  → ->boot()
             ├── ->admin()  (always called)
             ├── wp_doing_ajax() ? ->ajax()
             ├── add_action('rest_api_init') → ->rest()
             ├── register_activation_hook    → ->activate()
             └── register_deactivation_hook  → ->deactivate()
```

---

## Request Flow (REST example)

```
HTTP Request → WordPress → rest_api_init
  → register_rest_route callback fires
    → Pipeline::call($request, ...)
      → Global middlewares (from configs/app.php api_middlewares)
      → Route-specific middlewares (from ->middleware(...))
      → Controller::method($request, ...routeParams)
    ← Response returned to WordPress
```

---

## Key Files to Read First

1. `src/Container.php` — 25 lines; the foundation
2. `src/Application.php` — `make()` + `boot()`
3. `src/Contracts/ServiceProvider.php` — lifecycle hooks interface
4. `src/Provider.php` — base class for modules
5. `src/ProviderManager.php` — how providers are wired to WP
6. `src/RouteManager.php` + `src/RouteDefinition.php` — routing
7. `src/Pipeline.php` — middleware chain
8. `src/Facades/Facade.php` — how facades resolve
9. `src/Helpers/main.php` — global helpers
