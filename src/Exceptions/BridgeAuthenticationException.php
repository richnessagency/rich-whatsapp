<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Exceptions;

use RuntimeException;

/**
 * The Bridge rejected our bearer token (HTTP 401/403). Check
 * RICH_WHATSAPP_BRIDGE_TOKEN. Never log the token itself.
 */
class BridgeAuthenticationException extends RuntimeException
{
    public static function create(string $endpoint, int $status): self
    {
        return new self(
            "The WhatsApp Bridge rejected the configured token for {$endpoint} (HTTP {$status}). "
            . 'Check RICH_WHATSAPP_BRIDGE_TOKEN.'
        );
    }
}