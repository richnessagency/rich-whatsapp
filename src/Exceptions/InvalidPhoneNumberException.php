<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Exceptions;

use InvalidArgumentException;

/**
 * Raised when a phone number cannot be normalized safely.
 */
class InvalidPhoneNumberException extends InvalidArgumentException
{
    public static function invalid(string $value, ?string $detail = null): self
    {
        $message = sprintf('Invalid WhatsApp phone number "%s".', $value);

        if ($detail !== null && $detail !== '') {
            $message .= " {$detail}";
        }

        return new self($message);
    }
}