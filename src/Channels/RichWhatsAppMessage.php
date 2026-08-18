<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Channels;

use RichnessAgency\RichWhatsApp\Enums\MediaType;

class RichWhatsAppMessage
{
    public ?string $phone = null;
    public string $text = '';
    public ?string $idempotencyKey = null;
    public ?string $mediaPath = null;
    public ?MediaType $mediaType = null;
    public ?string $mediaFilename = null;
    public ?string $mediaMime = null;
    public ?string $mediaCaption = null;

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    public static function create(string $text = ''): self
    {
        return new self($text);
    }

    public function to(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function text(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function idempotencyKey(string $key): self
    {
        $this->idempotencyKey = $key;
        return $this;
    }

    public function media(string $path, MediaType $type, ?string $filename = null, ?string $mime = null, ?string $caption = null): self
    {
        $this->mediaPath = $path;
        $this->mediaType = $type;
        $this->mediaFilename = $filename;
        $this->mediaMime = $mime;
        $this->mediaCaption = $caption;
        return $this;
    }
}
