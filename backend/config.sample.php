<?php
// Copy this file to config.php and adjust values for your Hostinger environment.
return [
    'db' => [
        'host' => getenv('FUT_DB_HOST') ?: 'localhost',
        'name' => getenv('FUT_DB_NAME') ?: 'FUT',
        'user' => getenv('FUT_DB_USER') ?: 'root',
        'pass' => getenv('FUT_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        // Update to a real sender that your host allows (e.g., support@yourdomain.com).
        'from' => getenv('FUT_MAIL_FROM') ?: 'no-reply@example.com',
        // Base URL used in verification emails; must point to your hosted verify endpoint.
        'verify_base_url' => getenv('FUT_VERIFY_BASE_URL') ?: 'https://example.com/api/verify.php?token=',
        // Base URL used in password reset emails.
        'reset_base_url' => getenv('FUT_RESET_BASE_URL') ?: 'https://example.com/reset-password.php?token=',
        // Optional base URL for the FUT games password reset flow (falls back to current host/games/auth/resetar.php).
        'reset_games_base_url' => getenv('FUT_RESET_GAMES_BASE_URL') ?: '',
        // SMTP settings (recommended for Hostinger and other providers).
        'smtp' => [
            'host' => getenv('FUT_SMTP_HOST') ?: '',
            'port' => getenv('FUT_SMTP_PORT') ?: 587,
            'user' => getenv('FUT_SMTP_USER') ?: '',
            'pass' => getenv('FUT_SMTP_PASS') ?: '',
            // Use 'tls' (587) or 'ssl' (465).
            'secure' => getenv('FUT_SMTP_SECURE') ?: 'tls',
        ],
    ],
    'app' => [
        'cap_min' => 618,
        'cap_max' => 648,
        'debug_reset_link' => getenv('FUT_DEBUG_RESET_LINK') ?: false,
    ],
];

