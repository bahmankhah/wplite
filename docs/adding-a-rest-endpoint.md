# Adding a REST Endpoint

WPLite provides a unified router for four types of WordPress routes. This guide
covers adding **REST API**, **Ajax**, **Admin**, and **Web** endpoints.

---

## REST API Endpoint

### 1. Create a Controller

```php
<?php
// src/Controllers/UserController.php

namespace App\Controllers;

class UserController
{
    /**
     * @param \WP_REST_Request $request
     * @param string $id  Route parameter from {id}
     */
    public function show($request, $id)
    {
        $user = get_userdata($id);

        if (!$user) {
            return new \WP_REST_Response(['error' => 'User not found'], 404);
        }

        return new \WP_REST_Response([
            'id'   => $user->ID,
            'name' => $user->display_name,
        ]);
    }

    public function store($request)
    {
        $data = $request->get_json_params();

        // Validate and create user...
        return new \WP_REST_Response(['created' => true], 201);
    }
}
```

### 2. Define the Route

In `routes/rest.php`:

```php
<?php

use WPLite\Facades\Route;
use App\Controllers\UserController;
use App\Middlewares\AuthMiddleware;

Route::rest(function ($route) {
    // GET /wp-json/{namespace}/users/{id}
    $route->get('/users/{id}', [UserController::class, 'show'])
          ->middleware(AuthMiddleware::class)
          ->name('user.show');

    // POST /wp-json/{namespace}/users
    $route->post('/users', [UserController::class, 'store'])
          ->middleware(AuthMiddleware::class);
});
```

The namespace defaults to the value in `configs/app.php` → `api.namespace`.
Override per-route with `->namespace('custom/v2')`.

### 3. Generate URLs for Named Routes

```php
$url = reverse('user.show', ['id' => 42]);
// → /wp-json/myplugin/v1/users/42
```

---

## Ajax Endpoint

In `routes/ajax.php`:

```php
<?php

use WPLite\Facades\Route;
use App\Controllers\FormController;

Route::ajax(function ($route) {
    // Registers wp_ajax_submit_form AND wp_ajax_nopriv_submit_form
    $route->post('submit_form', [FormController::class, 'handle']);
});
```

The route path becomes the Ajax action name. Both logged-in and logged-out hooks
are registered automatically.

---

## Admin Page Endpoint

In `routes/admin.php`:

```php
<?php

use WPLite\Facades\Route;
use App\Controllers\SettingsController;

Route::admin(function ($route) {
    // Creates a WordPress admin menu page
    $route->get('my-plugin-settings', [SettingsController::class, 'index']);
});
```

The route path becomes the menu slug.

---

## Web (Frontend) Endpoint

In `routes/web.php`:

```php
<?php

use WPLite\Facades\Route;
use App\Controllers\PageController;

Route::web(function ($route) {
    // Intercepts frontend URL via template_redirect
    $route->get('/custom-landing', [PageController::class, 'show']);
});
```

---

## Adding Middleware

Create a middleware class implementing `WPLite\Contracts\Middleware`:

```php
<?php

namespace App\Middlewares;

use WPLite\Contracts\Middleware;
use WPLite\Pipeline;

class AuthMiddleware implements Middleware
{
    public function handle($request, Pipeline $pipeline)
    {
        if (!is_user_logged_in()) {
            return new \WP_REST_Response(['error' => 'Unauthorized'], 401);
        }

        // Continue to next middleware / controller
        return $pipeline->next($request);
    }
}
```

Attach per-route:
```php
$route->get('/secret', [SecretController::class, 'index'])
      ->middleware(AuthMiddleware::class, LogMiddleware::class);
```

Or globally in `configs/app.php`:
```php
return [
    'api_middlewares' => [
        \WPLite\Middlewares\AppMiddleware::class,
        \App\Middlewares\AuthMiddleware::class,
    ],
];
```

---

## Request Flow

```
HTTP Request
  → WordPress hooks (rest_api_init / wp_ajax_ / admin_menu / template_redirect)
    → Pipeline::call()
      → Global middlewares (api_middlewares from config)
      → Route-specific middlewares
      → Controller::method($request, ...routeParams)
    ← Response
```

---

## Route Method Summary

| Method | Description |
|---|---|
| `$route->get($path, [$ctrl, $method])` | Register a GET route |
| `$route->post($path, [$ctrl, $method])` | Register a POST route |
| `->middleware(Class::class, ...)` | Attach middleware |
| `->namespace('custom/v1')` | Override REST namespace |
| `->name('route.name')` | Name the route (for `reverse()`) |

---

## Key Files

- **Route files**: `routes/rest.php`, `routes/ajax.php`, `routes/admin.php`, `routes/web.php`
- **Router**: `src/RouteManager.php`
- **Route object**: `src/RouteDefinition.php`
- **Pipeline**: `src/Pipeline.php`
- **Middleware contract**: `src/Contracts/Middleware.php`
- **Route loader**: `src/Providers/RouteServiceProvider.php`
