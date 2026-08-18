<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp;

use Illuminate\Support\ServiceProvider;
use RichnessAgency\RichWhatsApp\Console\Commands\InstallCommand;
use RichnessAgency\RichWhatsApp\Console\Commands\TestCommand;
use RichnessAgency\RichWhatsApp\Console\Commands\HealthCommand;
use RichnessAgency\RichWhatsApp\Console\Commands\StatusCommand;
use RichnessAgency\RichWhatsApp\Console\Commands\ReconnectCommand;
use RichnessAgency\RichWhatsApp\Console\Commands\LogoutCommand;
use RichnessAgency\RichWhatsApp\Contracts\BridgeClient;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;
use RichnessAgency\RichWhatsApp\Services\WhatsAppBridgeClient;
use RichnessAgency\RichWhatsApp\Services\WhatsAppService;
use RichnessAgency\RichWhatsApp\Channels\RichWhatsAppChannel;
use Illuminate\Support\Facades\Notification;

class RichWhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rich-whatsapp.php',
            'rich-whatsapp'
        );

        $this->app->singleton(BridgeClient::class, function ($app) {
            return new WhatsAppBridgeClient([
                'timeout' => config('rich-whatsapp.http_timeout', 10),
                'connect' => config('rich-whatsapp.connect_timeout', 3),
            ]);
        });

        $this->app->singleton(WhatsApp::class, WhatsAppService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rich-whatsapp');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/rich-whatsapp.php' => config_path('rich-whatsapp.php'),
            ], 'rich-whatsapp-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'rich-whatsapp-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/rich-whatsapp'),
            ], 'rich-whatsapp-views');

            $this->publishes([
                __DIR__ . '/../resources/css' => public_path('vendor/rich-whatsapp/css'),
            ], 'rich-whatsapp-assets');

            $this->commands([
                InstallCommand::class,
                TestCommand::class,
                HealthCommand::class,
                StatusCommand::class,
                ReconnectCommand::class,
                LogoutCommand::class,
            ]);
        }

        $this->registerRoutes();
        $this->registerNotificationChannel();
    }

    protected function registerRoutes(): void
    {
        if (! config('rich-whatsapp.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    protected function registerNotificationChannel(): void
    {
        Notification::extend('rich-whatsapp', function ($app) {
            return $app->make(RichWhatsAppChannel::class);
        });
    }
}
