<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

use RichnessAgency\RichWhatsApp\Enums\SessionStatus;

/**
 * Immutable view of the current session state as reported by the bridge
 * (plus bridge reachability information tracked by the package).
 */
final readonly class SessionInfo
{
    public function __construct(
        public SessionStatus $status,
        public ?string $phone = null,
        public ?string $lastConnectedAt = null,
        public ?string $lastDisconnectedAt = null,
        public ?string $lastError = null,
        public ?string $lastStatusChange = null,
        public bool $hasCredentials = false,
        public bool $bridgeOnline = true,
    ) {}

    public static function fromBridgePayload(array $payload, bool $bridgeOnline = true): self
    {
        $statusRaw = $payload['status'] ?? $payload['data']['status'] ?? null;
        $status = SessionStatus::fromBridge(is_string($statusRaw) ? $statusRaw : '');

        return new self(
            status: $status,
            phone: isset($payload['phone']) && is_string($payload['phone']) ? $payload['phone'] : null,
            lastConnectedAt: $payload['last_connected_at'] ?? null,
            lastDisconnectedAt: $payload['last_disconnected_at'] ?? null,
            lastError: $payload['last_error'] ?? null,
            lastStatusChange: $payload['last_status_change'] ?? null,
            hasCredentials: (bool) ($payload['has_credentials'] ?? false),
            bridgeOnline: $bridgeOnline,
        );
    }

    public static function bridgeOffline(): self
    {
        return new self(
            status: SessionStatus::BridgeOffline,
            bridgeOnline: false,
        );
    }
}