<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Console\Commands;

use Illuminate\Console\Command;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

class LogoutCommand extends Command
{
    protected $signature = 'rich-whatsapp:logout {--force : Force logout without confirmation}';

    protected $description = 'Logout and unlink the current WhatsApp session.';

    public function handle(WhatsApp $service): int
    {
        $force = $this->option('force');

        if (! $force) {
            $confirm = $this->confirm(
                'WARNING: Logging out will unlink this WhatsApp session and a new QR scan will be required. Are you sure you want to continue?',
                false
            );

            if (! $confirm) {
                $this->warn('Logout cancelled.');
                return 0;
            }
        }

        $this->info('Logging out of WhatsApp...');
        $session = $service->logout();

        $this->info("Logged out successfully. Status is now: {$session->status->label()}");

        return 0;
    }
}
