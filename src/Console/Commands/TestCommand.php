<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Console\Commands;

use Illuminate\Console\Command;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

class TestCommand extends Command
{
    protected $signature = 'rich-whatsapp:test {phone? : Optional phone number to send a test message to}';

    protected $description = 'Test the Rich WhatsApp configuration, bridge connection, and optional message send.';

    public function handle(WhatsApp $service): int
    {
        $this->info('Testing Rich WhatsApp integration configuration...');

        $enabled = $service->enabled();
        $this->line('Package Enabled: ' . ($enabled ? 'Yes' : 'No'));

        if (! $enabled) {
            $this->error('Rich WhatsApp is disabled in configuration. Enable it in your .env first.');
            return 1;
        }

        $configured = $service->bridgeConfigured();
        $this->line('Bridge Configuration: ' . ($configured ? 'Configured' : 'Missing URL/Token'));

        if (! $configured) {
            $this->error('Bridge URL or Token is missing. Configure RICH_WHATSAPP_BRIDGE_URL and RICH_WHATSAPP_BRIDGE_TOKEN.');
            return 1;
        }

        $this->info('Pinging the Node Bridge...');
        $session = $service->sessionStatus();

        if (! $session->bridgeOnline) {
            $this->error('CRITICAL: Node Bridge is unreachable. Ensure the Node Bridge service is running and accessible.');
            return 1;
        }

        $this->info('Bridge is reachable!');
        $this->line('Session Status: ' . $session->status->label());
        if ($session->phone) {
            $this->line('Connected Phone: ' . $session->phone);
        }

        $phoneArg = $this->argument('phone');

        if ($phoneArg) {
            $this->info("Sending test message to: {$phoneArg}");

            if (! $session->status->isConnected()) {
                $this->error('Cannot send message. WhatsApp is not connected. Connect via the dashboard or scan the QR code first.');
                return 1;
            }

            try {
                $result = $service->sendText($phoneArg, 'Test message from Rich WhatsApp Laravel Package integration test command.');

                if ($result->successful()) {
                    $this->info('Test message request submitted successfully!');
                    $this->line("Request ID: {$result->requestId}");
                    if ($result->messageId) {
                        $this->line("Message ID: {$result->messageId}");
                    }
                    $this->line("Status: {$result->status->value}");
                } else {
                    $this->error("Failed to send message: {$result->error}");
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error("Error sending test message: {$e->getMessage()}");
                return 1;
            }
        } else {
            $this->info('No phone number provided. Skipping test message send.');
            $this->comment('To send a test message, run: php artisan rich-whatsapp:test <phone_number>');
        }

        $this->newLine();
        $this->info('All diagnostics passed successfully.');

        return 0;
    }
}
