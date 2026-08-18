<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Contracts;

use RichnessAgency\RichWhatsApp\Enums\MediaType;

/**
 * Fluent message builder created by WhatsApp::to().
 *
 * Example:
 *   WhatsApp::to('201012345678')->idempotencyKey('order-1')->send('Hi');
 */
interface PendingMessage
{
    public function idempotencyKey(string $key): static;

    public function message(string $text): static;

    public function text(string $text): static;

    public function caption(string $caption): static;

    /** Sends the text message (uses the text already set or the given one). */
    public function send(?string $text = null): mixed;

    public function sendText(?string $text = null): mixed;

    public function sendImage(string $path, ?string $caption = null): mixed;

    public function sendDocument(string $path, ?string $filename = null, ?string $mime = null): mixed;

    public function sendFile(string $path, MediaType $type, ?string $filename = null, ?string $mime = null): mixed;

    /** Returns the resolved normalized phone number. */
    public function phone(): string;

    public function idempotencyKeyValue(): ?string;
}