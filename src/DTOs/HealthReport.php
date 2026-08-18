<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

use RichnessAgency\RichWhatsApp\Enums\SessionStatus;

/**
 * Safe health snapshot used by the dashboard and the health command.
 * Never contains tokens, QR payloads or session credentials.
 */
final readonly class HealthReport
{
    public function __construct(
        public bool $packageEnabled,
        public bool $bridgeOnline,
        public ?float $bridgeLatencyMs,
        public SessionStatus $sessionStatus,
        public ?string $phone,
        public ?string $nodeUptime,
        public int $queuePending,
        public int $callbackBacklog,
        public ?string $lastActivityAt,
        public array $raw = [],
    ) {}

    public function isHealthy(): bool
    {
        return $this->packageEnabled
            && $this->bridgeOnline
            && $this->sessionStatus->isConnected();
    }
}