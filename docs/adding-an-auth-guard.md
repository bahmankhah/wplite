# Adding an Auth Guard

WPLite uses the **Adapter pattern** for authentication guards. The framework
ships with an SSO/OAuth2 guard. This guide shows how to add a custom one.

---

## 1. Implement the Guard Interface

Create a class that extends `Adapter` and implements `Guard`:

```php
<?php
// src/Auth/Guards/JwtGuard.php

namespace App\Auth\Guards;

use WPLite\Adapters\Adapter;
use WPLite\Contracts\Auth\Guard;

class JwtGuard extends Adapter implements Guard
{
    private $currentUser = null;

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user()
    {
        if ($this->currentUser) {
            return $this->currentUser;
        }

        $token = $this->extractToken();
        if (!$token) {
            return null;
        }

        try {
            $payload = $this->verifyToken($token);
            $this->currentUser = get_user_by('id', $payload['sub']);
            return $this->currentUser;
        } catch (\Exception $e) {
            appLogger('JWT verification failed: ' . $e->getMessage());
            return null;
        }
    }

    public function login($user)
    {
        $this->currentUser = $user;
        // Issue a JWT token (implementation depends on your JWT library)
    }

    public function logout()
    {
        $this->currentUser = null;
        // Invalidate the token if using a blocklist
    }

    public function attempt(array $credential)
    {
        $user = wp_authenticate($credential['username'], $credential['password']);

        if (is_wp_error($user)) {
            return false;
        }

        $this->login($user);
        return $user;
    }

    private function extractToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function verifyToken(string $token): array
    {
        $secret = $this->config['secret_key'];
        // Decode and verify JWT (using your preferred library)
        // Return the payload array with at least 'sub' (user ID)
        return [];
    }
}
```

## 2. Register in Config

In `configs/adapters.php`:

```php
return [
    'auth' => [
        'default' => 'jwt',
        'contexts' => [
            'sso' => [
                'context'      => \WPLite\Auth\Guards\SSOGuard::class,
                'client_id'    => getenv('SSO_CLIENT_ID'),
                'login_url'    => getenv('SSO_LOGIN_URL'),
                'validate_url' => getenv('SSO_VALIDATE_URL'),
                'redirect_url' => getenv('SSO_REDIRECT_URL'),
            ],
            'jwt' => [
                'context'    => \App\Auth\Guards\JwtGuard::class,
                'secret_key' => getenv('JWT_SECRET'),
            ],
        ],
    ],
];
```

## 3. Use It

```php
use WPLite\Facades\Auth;

// Uses the default guard ('jwt')
if (Auth::check()) {
    $user = Auth::user();
}

// Use a specific guard
Auth::use('sso')->logout();
```

---

## Guard Interface Reference

```php
interface Guard
{
    public function check(): bool;
    public function user();
    public function login($user);
    public function logout();
    public function attempt(array $credential);
}
```

---

## Key Files

- **Contract**: `src/Contracts/Auth/Guard.php`
- **User contract**: `src/Contracts/Auth/Authenticatable.php`
- **Built-in guard**: `src/Auth/Guards/SSOGuard.php`
- **Base adapter**: `src/Adapters/Adapter.php`
- **Adapter manager**: `src/Adapters/AdapterManager.php`
- **Facade**: `src/Facades/Auth.php`
- **Config**: `configs/adapters.php`
