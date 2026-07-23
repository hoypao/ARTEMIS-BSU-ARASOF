<?php

/*
 * ARTEMIS-specific settings (mail via PHPMailer + Google reCAPTCHA), read from
 * .env the Laravel way so `php artisan config:cache` stays safe.
 */
return [
    'mail' => [
        'host'      => env('MAIL_HOST', 'smtp.gmail.com'),
        'port'      => env('MAIL_PORT', '587'),
        'username'  => env('MAIL_USERNAME', ''),
        'password'  => env('MAIL_PASSWORD', ''),
        'from_name' => env('MAIL_FROM_NAME', 'ARTEMIS'),
    ],
    'recaptcha' => [
        'site_key'   => env('RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
    ],
];
