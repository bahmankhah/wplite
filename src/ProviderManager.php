<?php

namespace WPLite;

use WPLite\Facades\App;

/**
 * ProviderManager — discovers, instantiates, and wires service providers.
 *
 * Role: The orchestrator that connects Provider lifecycle hooks to WordPress
 *       action hooks. Called once during Application::boot().
 *
 * Responsibilities:
 *   - Merge built-in providers, config-registered providers, and
 *     auto-discovered providers (Src\Provider\* namespace).
 *   - Instantiate each provider and call register() + bootEarly().
 *   - Wire onInit/boot/admin/ajax/rest/activate/deactivate/uninstall
 *     to the corresponding WordPress hooks.
 *
 * How to use:
 *   - This is an internal framework class. You should not call it directly.
 *   - To add providers, use configs/app.php or the Src\Provider\ namespace.
 *
 * Avoid:
 *   - Do not call loadProviders() manually; Application::boot() handles it.
 *   - Do not modify the provider loading order unless necessary.
 *
 * @see \WPLite\Provider      Base provider class.
 * @see \WPLite\Application    Calls loadProviders() during boot().
 */
class ProviderManager
{
    protected $providers = [];
    protected $instances = [];

    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public static function loadProviders()
    {
        $providers = [
            \WPLite\Providers\RouteServiceProvider::class,
        ];
        $providers = array_merge($providers, appConfig('app.providers', []));
        foreach (get_declared_classes() as $declared) {
            if (strpos($declared, 'Src\\Provider\\') === 0 && !in_array($declared, $providers, true)) {
                $providers[] = $declared;
            }
        }
        $manager = new self($providers);
        $manager->load();
    }

    public function load()
    {
        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass();
            $this->instances[] = $provider;

            $provider->register();
            $provider->bootEarly();
        }

        add_action('init', function () {
            foreach ($this->instances as $provider) {
                $provider->onInit();
            }
        });

        add_action('wp_loaded', function () {
            foreach ($this->instances as $provider) {
                $provider->boot();
            }
        });

        foreach ($this->instances as $provider) {
            $provider->admin();
        }

        if (wp_doing_ajax()) {
            foreach ($this->instances as $provider) {
                $provider->ajax();
            }
        }

        add_action('rest_api_init', function () {
            foreach ($this->instances as $provider) {
                $provider->rest();
            }
        });
        register_activation_hook(App::pluginFile(), function () {
            foreach ($this->instances as $provider) {
                $provider->activate();
            }
        });
        register_deactivation_hook(App::pluginFile(), function () {
            foreach ($this->instances as $provider) {
                $provider->deactivate();
            }
        });
        register_uninstall_hook(App::pluginFile(), [$this->instances, 'uninstall']);
    }
}
