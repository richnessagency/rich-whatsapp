<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Exceptions;

use RuntimeException;

/**
 * The Node Bridge could not be reached (offline, DNS, TLS, timeout or 5xx).
 * The host application must remain fully operational when this happens.
 */
class BridgeUnavailableException extends RuntimeException
{
    public static function forRequest(string $endpoint, ?string $detail = null): self
    {
        $message = "The WhatsApp Bridge is unavailable while calling {$endpoint}.";

        if ($detail !== null && $detail !== '') {
            $message .= " {$detail}";
        }

        return new self($message);
    }
}