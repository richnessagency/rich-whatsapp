<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'rich-whatsapp:install';

    protected $description = 'Install the Rich WhatsApp package config, migrations, and assets.';

    public function handle(): int
    {
        $this->info('Installing Rich WhatsApp Package...');

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'rich-whatsapp-config',
        ]);

        $this->info('Publishing database migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'rich-whatsapp-migrations',
        ]);

        $this->info('Publishing assets...');
        $this->call('vendor:publish', [
            '--tag' => 'rich-whatsapp-assets',
        ]);

        if ($this->confirm('Would you like to run the database migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('Rich WhatsApp installed successfully!');
        $this->newLine();
        $this->comment('Next Steps:');
        $this->line('1. Configure these environment variables in your .env:');
        $this->info('   RICH_WHATSAPP_ENABLED=true');
        $this->info('   RICH_WHATSAPP_BRIDGE_URL=https://your-node-bridge.com');
        $this->info('   RICH_WHATSAPP_BRIDGE_TOKEN=your-bridge-secret-token');
        $this->info('   RICH_WHATSAPP_CALLBACK_TOKEN=your-callback-secret-token');
        $this->newLine();
        $this->line('2. Configure the Node Bridge callback URL to point to:');
        $this->info('   ' . url('/rich-whatsapp/api/callback'));
        $this->newLine();
        $this->line('3. Test the setup with:');
        $this->info('   php artisan rich-whatsapp:test');
        $this->newLine();
        $this->line('4. Open the dashboard at:');
        $this->info('   ' . url(config('rich-whatsapp.dashboard_prefix', 'whatsapp')));
        $this->newLine();

        return 0;
    }
}
