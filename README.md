# WPLite

WPLite is a lightweight PHP framework designed to simplify WordPress plugin and application development. It provides a modular architecture, dependency injection, routing, middleware, and other modern features for building scalable WordPress solutions.

## Features
- Modular structure with service providers
- Dependency injection container
- Routing and middleware support
- Facades for common services
- Database abstraction
- Authentication and guards
- Resource and view management

## Directory Structure
```
composer.json           # Composer dependencies and autoloading
src/
   wplite-sample.php     # Sample plugin main file (not entry point)
  WPLite/
    Application.php     # Core application class
    Config.php          # Configuration management
    Container.php       # Dependency injection container
    DB.php              # Database abstraction
    ...                 # Other core classes
    Adapters/           # Adapter pattern implementations
    Auth/               # Authentication and guards
    Contracts/          # Interfaces and contracts
    Facades/            # Facade classes for services
    Helpers/            # Helper functions
    Middlewares/        # Middleware implementations
    Providers/          # Service providers
vendor/                 # Composer dependencies
```

## Getting Started
1. **Install dependencies:**
   ```bash
   composer install
   ```
2. **Using WPLite in your plugin:**
   Refer to `src/wplite-sample.php` for an example of how to use WPLite in your own WordPress plugin main file. This file demonstrates how to bootstrap and run the WPLite application within a plugin context. It is not the entry point of the package itself.
3. **Configuration:**
   Edit `src/WPLite/Config.php` or provide your own config files as needed.
4. **Extend functionality:**
   - Define your routes in `src/routes` (recommended)
   - Register new service providers
   - Implement custom middleware, models, and views

## Contributing
Pull requests and issues are welcome! Please follow PSR standards and write tests for new features.

## License
See `vendor/LICENSE` for details.
