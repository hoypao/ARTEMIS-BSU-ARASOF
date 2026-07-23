<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Defines the legacy ARTEMIS constants so the ported views and
     * app/Support function libraries run unchanged. APP_URL follows the
     * actual request host (falls back to config('app.url') in console),
     * so links/assets stay correct whether the app is browsed via
     * 127.0.0.1:8000, localhost:8000, or an Apache subfolder later.
     */
    public function boot(): void
    {
        if (!defined('APP_URL')) {
            $base = $this->app->runningInConsole()
                ? config('app.url')
                : request()->getSchemeAndHttpHost() . request()->getBaseUrl();
            define('APP_URL', rtrim($base, '/'));
        }

        defined('APP_NAME') || define('APP_NAME', config('app.name'));
        defined('ARTEMIS_ROOT') || define('ARTEMIS_ROOT', public_path());
        defined('ARTEMIS_UPLOAD_PATH') || define('ARTEMIS_UPLOAD_PATH', public_path('uploads/documents'));
        defined('ARTEMIS_PROFILE_PHOTO_PATH') || define('ARTEMIS_PROFILE_PHOTO_PATH', public_path('uploads/profile_photos'));

        defined('MAIL_HOST') || define('MAIL_HOST', config('artemis.mail.host'));
        defined('MAIL_PORT') || define('MAIL_PORT', config('artemis.mail.port'));
        defined('MAIL_USERNAME') || define('MAIL_USERNAME', config('artemis.mail.username'));
        defined('MAIL_PASSWORD') || define('MAIL_PASSWORD', config('artemis.mail.password'));
        defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', config('artemis.mail.from_name'));

        defined('RECAPTCHA_SITE_KEY') || define('RECAPTCHA_SITE_KEY', config('artemis.recaptcha.site_key'));
        defined('RECAPTCHA_SECRET_KEY') || define('RECAPTCHA_SECRET_KEY', config('artemis.recaptcha.secret_key'));
    }
}
