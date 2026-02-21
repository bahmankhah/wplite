# Creating a Service Provider (Module)

Service providers are the primary organizational unit in WPLite. Each provider
represents a **feature module** with lifecycle hooks mapped to WordPress actions.

---

## 1. Create the Provider Class

Extend `WPLite\Provider` and override only the hooks you need:

```php
<?php

namespace Src\Provider;

use WPLite\Provider;
use WPLite\Facades\App;

class PaymentProvider extends Provider
{
    /**
     * Bind services to the container. Runs immediately.
     */
    public function register()
    {
        App::bind('payment.gateway', function () {
            return new \App\Services\StripeGateway(appConfig('payment.stripe_key'));
        });
    }

    /**
     * Runs on WordPress 'init' hook.
     * Good for registering post types, taxonomies, shortcodes.
     */
    public function onInit()
    {
        // Register a custom post type
        register_post_type('transaction', [
            'public' => true,
            'label'  => 'Transactions',
        ]);
    }

    /**
     * Runs on 'wp_loaded' — WordPress is fully loaded.
     * Good for feature logic that depends on other plugins being ready.
     */
    public function boot()
    {
        // Schedule a cron event if not already scheduled
        if (!wp_next_scheduled('process_pending_payments')) {
            wp_schedule_event(time(), 'hourly', 'process_pending_payments');
        }
    }

    /**
     * Runs only in wp-admin context.
     */
    public function admin()
    {
        if (is_admin()) {
            add_action('admin_notices', function () {
                if (!appConfig('payment.stripe_key')) {
                    echo '<div class="notice notice-warning"><p>Payment gateway key not configured.</p></div>';
                }
            });
        }
    }

    /**
     * Runs on plugin activation.
     */
    public function activate()
    {
        // Create the transactions table
        global $wpdb;
        $table = $wpdb->prefix . 'transactions';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Runs on plugin deactivation.
     */
    public function deactivate()
    {
        wp_clear_scheduled_hook('process_pending_payments');
    }
}
```

---

## 2. Register the Provider

**Option A — Config registration** (in `configs/app.php`):

```php
return [
    'providers' => [
        \Src\Provider\PaymentProvider::class,
    ],
];
```

**Option B — Auto-discovery**: Place the class under the `Src\Provider\` namespace.
`ProviderManager` automatically discovers all classes in that namespace.

---

## 3. Available Lifecycle Hooks

| Hook | WordPress timing | Typical use |
|---|---|---|
| `register()` | Immediately | Bind services, set config |
| `bootEarly()` | Immediately (after register) | Pre-init setup |
| `onInit()` | `init` action | Post types, taxonomies, rewrite rules |
| `boot()` | `wp_loaded` action | Feature logic, cron registration |
| `admin()` | Always called | Admin notices, menu items (check `is_admin()`) |
| `ajax()` | Only when `wp_doing_ajax()` | Ajax handler setup |
| `rest()` | `rest_api_init` action | REST route registration |
| `activate()` | Plugin activation hook | DB migrations, default options |
| `deactivate()` | Plugin deactivation hook | Cleanup, remove cron |
| `uninstall()` | Plugin uninstall hook | Delete all plugin data |

---

## 4. Guidelines

- **Keep `register()` fast** — only bind services, don't execute WP functions.
- **One feature per provider** — keeps modules focused and testable.
- **Use the container** — `App::bind()` in register, `App::resolve()` or `App::make()` later.
- **Namespace under `Src\Provider\`** for auto-discovery, or register explicitly in config.

---

## Base Class Reference

- **Extend**: `WPLite\Provider`
- **Interface**: `WPLite\Contracts\ServiceProvider`
- **Loaded by**: `WPLite\ProviderManager`
