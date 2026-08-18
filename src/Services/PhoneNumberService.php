<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Services;

use RichnessAgency\RichWhatsApp\Exceptions\InvalidPhoneNumberException;

/**
 * Converts common user phone input into a bare E.164 international number
 * (digits only) expected by the Node Bridge.
 *
 * Accepted input: 01012345678, 201012345678, +201012345678, +20 10 1234 5678.
 * Guessing is bounded: a leading '+' or '00' means the caller already provided
 * the country code; a leading '0' is a national number that gets the default
 * country code; otherwise the number is assumed to already be international
 * when it starts with the default country code, and prepends it otherwise.
 */
class PhoneNumberService
{
    public function normalize(string $phone, ?string $defaultCountryCode = null): string
    {
        $defaultCountryCode ??= (string) config('rich-whatsapp.default_country_code', '');

        $value = trim($phone);

        if (str_contains($value, '@')) {
            return $value;
        }

        if ($value === '') {
            throw InvalidPhoneNumberException::invalid($value, 'The phone number is empty.');
        }

        $explicitPlus = str_starts_with($value, '+');
        $explicitDoubleZero = str_starts_with($value, '00');

        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            throw InvalidPhoneNumberException::invalid($value, 'It contains no digits.');
        }

        // Explicit + or 00: the caller provided the country code themselves.
        if ($explicitPlus) {
            return $this->validateInternational($digits, $value);
        }

        if ($explicitDoubleZero) {
            $without = ltrim($digits, '0');
            if ($without === '') {
                throw InvalidPhoneNumberException::invalid($value);
            }

            return $this->validateInternational($without, $value);
        }

        if ($defaultCountryCode === '') {
            // No default configured: a bare national number cannot be resolved.
            if (str_starts_with($digits, '0')) {
                throw InvalidPhoneNumberException::invalid(
                    $value,
                    'RICH_WHATSAPP_DEFAULT_COUNTRY_CODE is empty, use the international +... format.'
                );
            }

            return $this->validateInternational($digits, $value);
        }

        // National number with a leading zero.
        if (str_starts_with($digits, '0')) {
            $national = ltrim($digits, '0');

            if ($national === '') {
                throw InvalidPhoneNumberException::invalid($value);
            }

            return $this->validateInternational($defaultCountryCode.$national, $value);
        }

        // Already international (starts with the default country code) or a
        // local number without leading zero. Prepend when needed.
        $normalized = str_starts_with($digits, $defaultCountryCode)
            ? $digits
            : $defaultCountryCode.$digits;

        return $this->validateInternational($normalized, $value);
    }

    /** @throws InvalidPhoneNumberException */
    protected function validateInternational(string $digits, string $original): string
    {
        if (! preg_match('/^\d{7,15}$/', $digits)) {
            throw InvalidPhoneNumberException::invalid(
                $original,
                'An international number must contain 7 to 15 digits.'
            );
        }

        return $digits;
    }
}