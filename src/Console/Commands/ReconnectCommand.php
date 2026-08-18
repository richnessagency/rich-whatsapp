<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Console\Commands;

use Illuminate\Console\Command;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

class ReconnectCommand extends Command
{
    protected $signature = 'rich-whatsapp:reconnect';

    protected $description = 'Trigger manual WhatsApp reconnection on the Node Bridge.';

    public function handle(WhatsApp $service): int
    {
        $this->info('Triggering reconnect on the Node Bridge...');
        $session = $service->reconnect();

        $this->info("Reconnect triggered. Status is now: {$session->status->label()}");

        return 0;
    }
}
