<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Tests\Feature;

use RichnessAgency\RichWhatsApp\Contracts\BridgeClient;
use RichnessAgency\RichWhatsApp\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class CommandTest extends TestCase
{
    protected MockInterface $clientMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->clientMock = Mockery::mock(BridgeClient::class);
        $this->app->instance(BridgeClient::class, $this->clientMock);
    }

    public function test_install_command_runs_successfully(): void
    {
        $this->artisan('rich-whatsapp:install')
            ->expectsConfirmation('Would you like to run the database migrations now?', 'no')
            ->assertExitCode(0);
    }

    public function test_status_command_displays_correctly(): void
    {
        $this->clientMock->shouldReceive('sessionStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'connected',
                'phone' => '201012345678',
                'has_credentials' => true,
            ]);

        $this->artisan('rich-whatsapp:status')
            ->expectsTable(
                ['Property', 'Value'],
                [
                    ['Bridge Status', 'Online'],
                    ['WhatsApp Status', 'Connected'],
                    ['Connected Phone', '201012345678'],
                    ['Credentials Loaded', 'Yes'],
                    ['Last Connected At', 'N/A'],
                    ['Last Disconnected At', 'N/A'],
                    ['Last Error', 'None'],
                ]
            )
            ->assertExitCode(0);
    }

    public function test_health_command_shows_health_report(): void
    {
        $this->clientMock->shouldReceive('healthDetails')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'whatsapp' => [
                        'status' => 'connected',
                        'phone' => '201012345678',
                    ],
                    'service' => [
                        'uptime_seconds' => 3600,
                    ],
                    'queue' => [
                        'pending' => 0,
                    ],
                    'callbacks' => [
                        'pending' => 0,
                    ],
                ]
            ]);

        $this->artisan('rich-whatsapp:health')
            ->expectsOutput('=== Rich WhatsApp Integration Health ===')
            ->assertExitCode(0);
    }

    public function test_reconnect_command_sends_reconnect_request(): void
    {
        $this->clientMock->shouldReceive('reconnect')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'connecting',
            ]);

        $this->artisan('rich-whatsapp:reconnect')
            ->expectsOutput('Reconnect triggered. Status is now: Connecting')
            ->assertExitCode(0);
    }

    public function test_logout_command_asks_confirmation_and_logs_out(): void
    {
        $this->clientMock->shouldReceive('logout')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'logged_out',
            ]);

        $this->artisan('rich-whatsapp:logout')
            ->expectsConfirmation('WARNING: Logging out will unlink this WhatsApp session and a new QR scan will be required. Are you sure you want to continue?', 'yes')
            ->expectsOutput('Logged out successfully. Status is now: Logged Out')
            ->assertExitCode(0);
    }

    public function test_test_command_verifies_connection_and_setup(): void
    {
        $this->clientMock->shouldReceive('sessionStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'connected',
                'phone' => '201012345678',
                'has_credentials' => true,
            ]);

        $this->artisan('rich-whatsapp:test')
            ->expectsOutput('Bridge is reachable!')
            ->assertExitCode(0);
    }
}
