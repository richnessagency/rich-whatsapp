<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Console\Commands;

use Illuminate\Console\Command;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

class HealthCommand extends Command
{
    protected $signature = 'rich-whatsapp:health';

    protected $description = 'Display comprehensive health stats of the WhatsApp integration.';

    public function handle(WhatsApp $service): int
    {
        $this->info('Fetching health details...');

        $report = $service->health();

        $this->info('=== Rich WhatsApp Integration Health ===');
        $this->line("Package Enabled:        " . ($report->packageEnabled ? 'Yes' : 'No'));
        $this->line("Bridge Online:          " . ($report->bridgeOnline ? 'Yes' : 'No'));
        $this->line("Bridge Latency:         " . ($report->bridgeLatencyMs !== null ? $report->bridgeLatencyMs . ' ms' : 'N/A'));
        $this->line("Session Status:         " . $report->sessionStatus->label());
        $this->line("Connected Phone:        " . ($report->phone ?? 'N/A'));
        $this->line("Node Uptime:            " . ($report->nodeUptime ?? 'N/A'));
        $this->line("Queue Pending Messages: " . $report->queuePending);
        $this->line("Callback Backlog:       " . $report->callbackBacklog);
        $this->line("Last Activity At:       " . ($report->lastActivityAt ?? 'N/A'));
        $this->line("Overall Status:         " . ($report->isHealthy() ? 'HEALTHY' : 'UNHEALTHY'));

        return $report->isHealthy() ? 0 : 1;
    }
}
