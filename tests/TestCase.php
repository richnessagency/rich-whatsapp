<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use RichnessAgency\RichWhatsApp\RichWhatsAppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            RichWhatsAppServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('rich-whatsapp.enabled', true);
        $app['config']->set('rich-whatsapp.bridge_url', 'http://bridge.test');
        $app['config']->set('rich-whatsapp.bridge_token', 'bridge-token-for-tests');
        $app['config']->set('rich-whatsapp.callback_token', 'callback-token-for-tests');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
