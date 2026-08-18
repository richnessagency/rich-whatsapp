<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Console\Commands;

use Illuminate\Console\Command;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

class StatusCommand extends Command
{
    protected $signature = 'rich-whatsapp:status';

    protected $description = 'Check and display the current WhatsApp session status.';

    public function handle(WhatsApp $service): int
    {
        $this->info('Checking WhatsApp session status...');

        $session = $service->sessionStatus();

        $this->table(
            ['Property', 'Value'],
            [
                ['Bridge Status', $session->bridgeOnline ? 'Online' : 'Offline'],
                ['WhatsApp Status', $session->status->label()],
                ['Connected Phone', $session->phone ?? 'None'],
                ['Credentials Loaded', $session->hasCredentials ? 'Yes' : 'No'],
                ['Last Connected At', $session->lastConnectedAt ?? 'N/A'],
                ['Last Disconnected At', $session->lastDisconnectedAt ?? 'N/A'],
                ['Last Error', $session->lastError ?? 'None'],
            ]
        );

        return 0;
    }
}
