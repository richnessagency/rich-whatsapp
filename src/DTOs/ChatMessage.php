<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

/**
 * Immutable view of a single message from the bridge chat history.
 * Media content is never embedded here; it is served on demand from the
 * bridge through the package's authenticated media proxy route.
 */
final readonly class ChatMessage
{
    public function __construct(
        public ?string $id,
        public bool $fromMe,
        public ?string $from,
        public ?string $participant,
        public ?string $timestamp,
        public string $type,
        public ?string $text,
        public ?string $caption,
        public ?string $mimetype,
        public ?string $filename,
        public ?int $duration,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $contactName,
        public bool $isMedia,
    ) {}

    public static function fromBridge(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (string) $data['id'] : null,
            fromMe: (bool) ($data['from_me'] ?? false),
            from: isset($data['from']) ? (string) $data['from'] : null,
            participant: isset($data['participant']) ? (string) $data['participant'] : null,
            timestamp: isset($data['timestamp']) && is_string($data['timestamp'])
                ? $data['timestamp']
                : null,
            type: (string) ($data['type'] ?? 'text'),
            text: isset($data['text']) ? (string) $data['text'] : null,
            caption: isset($data['caption']) ? (string) $data['caption'] : null,
            mimetype: isset($data['mimetype']) ? (string) $data['mimetype'] : null,
            filename: isset($data['filename']) ? (string) $data['filename'] : null,
            duration: isset($data['duration']) && is_numeric($data['duration'])
                ? (int) $data['duration']
                : null,
            latitude: isset($data['latitude']) && is_numeric($data['latitude'])
                ? (float) $data['latitude']
                : null,
            longitude: isset($data['longitude']) && is_numeric($data['longitude'])
                ? (float) $data['longitude']
                : null,
            contactName: isset($data['contact_name']) ? (string) $data['contact_name'] : null,
            isMedia: (bool) ($data['is_media'] ?? false),
        );
    }

    public function displayText(): string
    {
        if ($this->isMedia) {
            $label = match ($this->type) {
                'image' => '🖼️ Image',
                'video' => '🎬 Video',
                'audio' => '🎵 Audio',
                'document' => '📄 ' . ($this->filename ?: 'Document'),
                'sticker' => '🧸 Sticker',
                default => ucfirst($this->type),
            };

            return $this->caption ? $label . ' — ' . $this->caption : $label;
        }

        return $this->text ?? ($this->type === 'location' && $this->latitude !== null
            ? '📍 ' . $this->latitude . ', ' . $this->longitude
            : '');
    }

    public function time(): ?string
    {
        if ($this->timestamp === null) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($this->timestamp))->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }
}