<?php

namespace WPLite\Auth\Guards;

use WPLite\Adapters\Adapter;
use WPLite\Contracts\Auth\Guard;

class SSOGuard extends Adapter implements Guard
{

    public function getLoginUrl()
    {
        return replacePlaceholders($this->config['login_url'], ['clientId' => $this->config['client_id'], 'redirectUrl' => $this->config['redirect_url']]);
    }
    public function check(): bool
    {
        $user = wp_get_current_user();

        if ($user && $user->ID) {
            $expiresAt = (int) get_user_meta($user->ID, 'sso_expires_at', true);

            if (time() >= $expiresAt) {
                $this->refreshToken($user);
            }
            return true;
        }

        return false;
    }


    public function user()
    {
    }

    public function login($user)
    {
        // Establish current user context & auth cookies
        wp_set_current_user($user->ID);
        // Set persistent auth cookie (remember = true) so new tabs immediately send it
        wp_set_auth_cookie($user->ID, true);

        // Fire core hook so other plugins (cache, security, sessions) react properly
        do_action('wp_login', $user->user_login, $user);

        // Encourage downstream layers not to cache this response
        if (!headers_sent()) {
            nocache_headers();
        }
    }


    public function logout()
    {
        $user = wp_get_current_user();
        if ($user && $user->ID) {
            delete_user_meta($user->ID, 'sso_access_token');
            delete_user_meta($user->ID, 'sso_refresh_token');
            delete_user_meta($user->ID, 'sso_expires_at');
        }

        wp_logout();
    }



    public function attempt(array $credential)
    {
        appLogger('SSOGuard::attempt called with credential: ' . json_encode($credential));

        $api_url = $this->config['validate_url'];
        $api_url = trim($api_url, '"');
        $clientId = $this->config['client_id'];
        $redirect = isset($credential['redirect_url']) && !empty($credential['redirect_url']) ? $credential['redirect_url'] : $this->config['redirect_url'];
        // Exchange code for token
        appLogger('Raw API URL: ' . var_export($api_url, true));
        $response = wp_remote_post($api_url, [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'scope' => 'openid profile',
                'code' => $credential['code'],
                'redirect_uri' => $redirect,
                'session_state' => $credential['session_state'] ?? null,
            ],
        ]);

        appLogger('Token response: ' . json_encode($response));
        if (is_wp_error($response)) {
            appLogger('Error in token response: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        appLogger('Decoded token response body: ' . json_encode($body));

        if (!isset($body['access_token'])) {
            appLogger('No access_token found in response body');
            return false;
        }

        $jwt = $body['access_token'];
        // IMPORTANT: You must validate the JWT signature here before proceeding.
        $payload = $this->decodeJwt($jwt);
        appLogger('Decoded JWT payload: ' . json_encode($payload));
        if (!$payload || !isset($payload['sub'])) {
            appLogger('JWT payload invalid or missing sub');
            return false;
        }

        $globalId = $payload['sub'];
        $email = sanitize_email($payload['email'] ?? '');
        $user = null;

        // 1. Check for existing user by the unique SSO Global ID (most reliable)
        appLogger('Looking for user by sso_global_id: ' . $globalId);
        $users = get_users([
            'meta_key' => 'sso_global_id',
            'meta_value' => $globalId,
            'number' => 1,
            'count_total' => false,
        ]);

        if (!empty($users)) {
            $user = $users[0];
            appLogger('Found user by sso_global_id: ' . $user->ID);
        }

        // 2. If not found, check for an existing user by email (for first-time linking)
        if (!$user && !empty($email)) {
            appLogger('Looking for user by email: ' . $email);
            $existing_user_by_email = get_user_by('email', $email);
            if ($existing_user_by_email) {
                $user = $existing_user_by_email;
                appLogger('Found user by email: ' . $user->ID . ', linking sso_global_id');
                // This is the first SSO login for this user, so link the accounts.
                update_user_meta($user->ID, 'sso_global_id', $globalId);
            }
        }

        // 3. If still no user, fall back to your phone number logic or create a new user
        if (!$user && isset($payload['phoneNumber'])) {
            $mobileNumber = $payload['phoneNumber'] ?? '';
            appLogger($payload['phoneNumber']);
            $formattedMobile = formatMobile($mobileNumber); // Ensure the mobile is formatted
            appLogger('Looking for user by phone: ' . $formattedMobile);

            $users_by_phone = get_users([
                'meta_key' => 'digits_phone',
                'meta_value' => $formattedMobile,
                'number' => 1,
                'count_total' => false,
            ]);

            if (!empty($users_by_phone)) {
                $user = $users_by_phone[0];
                appLogger('Found user by phone: ' . $user->ID . ', linking sso_global_id');
                // Link account on first login via phone match
                update_user_meta($user->ID, 'sso_global_id', $globalId);
            } else {
                // Create a new user as no existing account was found
                $firstName = sanitize_text_field($payload['given_name'] ?? '');
                $lastName = sanitize_text_field($payload['family_name'] ?? '');
                $displayName = trim($firstName . ' ' . $lastName);
                $username = sanitize_user($payload['preferred_username'] ?? 'user_' . wp_generate_password(5, false));

                appLogger('Creating new user: ' . $username . ', email: ' . $email);
                $user_id = wp_create_user($username, wp_generate_password(), $email);
                if (is_wp_error($user_id)) {
                    appLogger('Failed to create user: ' . $user_id->get_error_message());
                    return false;
                }
                $user = get_user_by('id', $user_id);

                // Set metadata for new user
                update_user_meta($user_id, 'sso_global_id', $globalId);
                // update_user_meta($user_id, 'sso_phone_number', $formattedMobile);
                update_user_meta($user_id, 'sso_mobile_number', $mobileNumber);
                update_user_meta($user_id, 'sso_national_id', $body['nationalId']);
                appLogger('New user created and meta set: ' . $user_id);
            }
        }

        // Update user profile information from SSO for both existing and new users
        $firstName = sanitize_text_field($payload['given_name'] ?? '');
        $lastName = sanitize_text_field($payload['family_name'] ?? '');
        $displayName = trim($firstName . ' ' . $lastName);

        appLogger('Updating user profile: ' . $user->ID);
        wp_update_user([
            'ID' => $user->ID,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => $displayName,
        ]);

        // Update SSO tokens and expiration
        appLogger('Updating user SSO tokens: ' . $user->ID);
        update_user_meta($user->ID, 'sso_access_token', $body['access_token']);
        update_user_meta($user->ID, 'sso_refresh_token', $body['refresh_token']);
        update_user_meta($user->ID, 'sso_expires_at', time() + ($body['expires_in'] ?? 3600)); // Use expires_in

        // Log in the user
        appLogger('Logging in user: ' . $user->ID);
        $this->login($user);
        appLogger('User login complete: ' . $user->ID);

        return $user;
    }

    public function refreshToken($user)
    {
        $refreshToken = get_user_meta($user->ID, 'sso_refresh_token', true);
        if (!$refreshToken) {
            return false;
        }

        $response = wp_remote_post($this->config['validate_url'], [
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => $this->config['client_id'],
                'refresh_token' => $refreshToken,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['access_token'])) {
            return false;
        }

        update_user_meta($user->ID, 'sso_access_token', $body['access_token']);
        update_user_meta($user->ID, 'sso_refresh_token', $body['refresh_token']);
        update_user_meta($user->ID, 'sso_expires_at', time() + $body['exp']);

        return true;
    }
    private function decodeJwt($jwt)
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'));
        return json_decode($payload, true);
    }
}
