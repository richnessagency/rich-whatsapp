<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

/**
 * QR payload returned by the bridge. The bridge hands us a PNG data URL;
 * the raw scan payload never leaves it and is never persisted by this package.
 */
final readonly class QrData
{
    public function __construct(
        public string $qr,
        public ?string $expiresAt = null,
    ) {}

    public function isDataUrl(): bool
    {
        return str_starts_with($this->qr, 'data:image/');
    }
}