<?php
// Default config; replace values or use environment variables on Hostinger.
return [
    'db' => [
        'host' => getenv('FUT_DB_HOST') ?: 'localhost',
    'name' => getenv('FUT_DB_NAME') ?: 'u289267434_FUTbrasilbanco',
    'user' => getenv('FUT_DB_USER') ?: 'u289267434_FUTbrasilbanco',
    'pass' => getenv('FUT_DB_PASS') ?: 'FUTbrasil@2025',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
    'from' => getenv('FUT_MAIL_FROM') ?: 'no-reply@FUTbrasil.com.br',
    'verify_base_url' => getenv('FUT_VERIFY_BASE_URL') ?: 'https://FUTbrasil.com.br/api/verify.php?token=',
    'reset_base_url' => getenv('FUT_RESET_BASE_URL') ?: 'https://FUTbrasil.com.br/reset-password.php?token=',
    ],
    'app' => [
        'cap_min' => 618,
        'cap_max' => 648,
        'debug_reset_link' => getenv('FUT_DEBUG_RESET_LINK') ?: false,
    ],
];

