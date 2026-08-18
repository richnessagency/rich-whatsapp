<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Exceptions;

use RuntimeException;

/**
 * The Bridge rejected an outgoing message (validation, session not connected,
 * queue full, unrecoverable send failure).
 */
class MessageSendException extends RuntimeException
{
    /** @var array<string, mixed> Payload returned by the bridge when available. */
    public array $bridgePayload = [];

    public function withBridgePayload(array $payload): static
    {
        $this->bridgePayload = $payload;

        return $this;
    }
}