<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Services;

use RichnessAgency\RichWhatsApp\Contracts\PendingMessage as PendingMessageContract;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;
use RichnessAgency\RichWhatsApp\Enums\MediaType;

class PendingMessage implements PendingMessageContract
{
    protected string $phone;
    protected ?string $idempotencyKey = null;
    protected ?string $text = null;
    protected ?string $caption = null;

    public function __construct(
        protected WhatsApp $service,
        string $phone
    ) {
        $this->phone = $phone;
    }

    public function idempotencyKey(string $key): static
    {
        $this->idempotencyKey = $key;
        return $this;
    }

    public function message(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function caption(string $caption): static
    {
        $this->caption = $caption;
        return $this;
    }

    public function send(?string $text = null): mixed
    {
        $textToSend = $text ?: $this->text;
        return $this->service->sendText($this->phone, $textToSend ?? '', $this->idempotencyKey);
    }

    public function sendText(?string $text = null): mixed
    {
        return $this->send($text);
    }

    public function sendImage(string $path, ?string $caption = null): mixed
    {
        $resolvedCaption = $caption ?: $this->caption;
        return $this->service->sendMedia(
            phone: $this->phone,
            path: $path,
            type: MediaType::Image,
            caption: $resolvedCaption,
            idempotencyKey: $this->idempotencyKey
        );
    }

    public function sendDocument(string $path, ?string $filename = null, ?string $mime = null): mixed
    {
        return $this->service->sendMedia(
            phone: $this->phone,
            path: $path,
            type: MediaType::Document,
            filename: $filename,
            mime: $mime,
            caption: $this->caption,
            idempotencyKey: $this->idempotencyKey
        );
    }

    public function sendFile(string $path, MediaType $type, ?string $filename = null, ?string $mime = null): mixed
    {
        return $this->service->sendMedia(
            phone: $this->phone,
            path: $path,
            type: $type,
            filename: $filename,
            mime: $mime,
            caption: $this->caption,
            idempotencyKey: $this->idempotencyKey
        );
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function idempotencyKeyValue(): ?string
    {
        return $this->idempotencyKey;
    }
}
