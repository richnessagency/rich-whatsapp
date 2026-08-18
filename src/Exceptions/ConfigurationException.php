<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Exceptions;

use RuntimeException;

/**
 * The package is not properly configured (missing bridge URL/token while the
 * feature is enabled). Only surfaced inside WhatsApp functionality; unrelated
 * pages of the host application are never affected.
 */
class ConfigurationException extends RuntimeException
{
    public static function missing(string $what): self
    {
        return new self(
            'Rich WhatsApp is not configured: ' . $what
            . '. Set RICH_WHATSAPP_BRIDGE_URL and RICH_WHATSAPP_BRIDGE_TOKEN in your .env.'
        );
    }
}