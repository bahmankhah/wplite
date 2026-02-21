# Adding a Background Job

> **Note**: WPLite does not yet include a built-in Job/Queue abstraction.
> This guide shows how to implement background tasks using WordPress cron
> within the WPLite provider pattern.

---

## Using WordPress Cron in a Provider

### 1. Create a Job Class

```php
<?php
// src/Jobs/ProcessPendingPayments.php

namespace App\Jobs;

use WPLite\Facades\App;

class ProcessPendingPayments
{
    /**
     * Execute the job.
     */
    public function handle()
    {
        $gateway = App::resolve('payment.gateway');

        // Query pending payments
        $pending = (new \App\Models\Payment())
            ->where('status', '=', 'pending')
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->limit(50)
            ->get();

        foreach ($pending as $payment) {
            try {
                $gateway->process($payment);
            } catch (\Exception $e) {
                appLogger("Job failed for payment {$payment['id']}: " . $e->getMessage());
            }
        }
    }
}
```

### 2. Schedule the Job in a Provider

```php
<?php
// src/Provider/PaymentProvider.php

namespace Src\Provider;

use WPLite\Provider;
use App\Jobs\ProcessPendingPayments;

class PaymentProvider extends Provider
{
    public function boot()
    {
        // Register the cron action handler
        add_action('wplite_process_payments', function () {
            (new ProcessPendingPayments())->handle();
        });

        // Schedule the recurring event if not already scheduled
        if (!wp_next_scheduled('wplite_process_payments')) {
            wp_schedule_event(time(), 'hourly', 'wplite_process_payments');
        }
    }

    public function deactivate()
    {
        // Clean up the scheduled event on deactivation
        wp_clear_scheduled_hook('wplite_process_payments');
    }
}
```

### 3. One-Time (Delayed) Jobs

For a single deferred execution:

```php
// Schedule a one-time job 5 minutes from now
wp_schedule_single_event(
    time() + 300,
    'wplite_send_welcome_email',
    ['user_id' => $userId]
);

// Handler (in a provider's boot()):
add_action('wplite_send_welcome_email', function ($userId) {
    (new \App\Jobs\SendWelcomeEmail())->handle($userId);
});
```

---

## Custom Cron Intervals

WordPress ships with `hourly`, `twicedaily`, and `daily`. Add custom intervals:

```php
// In a provider's onInit():
public function onInit()
{
    add_filter('cron_schedules', function ($schedules) {
        $schedules['every_five_minutes'] = [
            'interval' => 300,
            'display'  => 'Every 5 Minutes',
        ];
        return $schedules;
    });
}
```

Then use `'every_five_minutes'` as the recurrence in `wp_schedule_event()`.

---

## Pattern Summary

| Step | Where | What |
|---|---|---|
| Create job class | `src/Jobs/YourJob.php` | Implement `handle()` method with job logic |
| Register action | Provider `boot()` | `add_action('your_hook', ...)` |
| Schedule event | Provider `boot()` | `wp_schedule_event()` or `wp_schedule_single_event()` |
| Cleanup | Provider `deactivate()` | `wp_clear_scheduled_hook('your_hook')` |

---

## Future: Job Base Class

When a `Job` abstraction is added to WPLite, it would likely look like:

```php
abstract class Job
{
    /** Cron hook name. Override in subclass. */
    protected string $hook;

    /** Recurrence: 'hourly', 'daily', 'once', etc. */
    protected string $recurrence = 'hourly';

    abstract public function handle();

    public function schedule(): void { /* auto-register with WP cron */ }
    public function unschedule(): void { /* auto-remove from WP cron */ }
}
```

Until then, use the provider-based pattern above.

---

## Conventions

- **Namespace**: `App\Jobs\{JobName}`
- **File location**: `src/Jobs/{JobName}.php`
- **Hook naming**: `wplite_{snake_case_action}` (prefix to avoid collisions)
- **Register in**: A provider's `boot()` method
- **Clean up in**: The same provider's `deactivate()` method
- **Log failures**: Use `appLogger()` for error tracking
