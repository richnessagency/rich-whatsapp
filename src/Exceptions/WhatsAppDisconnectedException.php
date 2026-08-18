<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Exceptions;

use RuntimeException;

/**
 * The WhatsApp session is not connected, so the requested operation cannot run.
 */
class WhatsAppDisconnectedException extends RuntimeException
{
    public static function create(string $operation = 'send message'): self
    {
        return new self("WhatsApp is not connected; cannot {$operation}.");
    }
}