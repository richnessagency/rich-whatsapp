<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

use RichnessAgency\RichWhatsApp\Enums\MessageStatus;

/**
 * Structured result of an outgoing message request. Never leak arbitrary
 * bridge JSON arrays; expose only what applications need.
 */
final readonly class MessageResult
{
    public function __construct(
        public bool $successful,
        public string $requestId,
        public ?string $messageId,
        public MessageStatus $status,
        public ?string $error = null,
    ) {}

    public static function failed(string $requestId, string $error, ?MessageStatus $status = null): self
    {
        return new self(
            successful: false,
            requestId: $requestId,
            messageId: null,
            status: $status ?? MessageStatus::Failed,
            error: $error,
        );
    }

    public static function fromBridge(string $requestId, array $bridgeData): self
    {
        $status = MessageStatus::tryFromBridge($bridgeData['status'] ?? null) ?? MessageStatus::Submitted;

        return new self(
            successful: true,
            requestId: $bridgeData['request_id'] ?? $requestId,
            messageId: isset($bridgeData['message_id']) && is_string($bridgeData['message_id'])
                ? $bridgeData['message_id']
                : null,
            status: $status,
        );
    }

    public function successful(): bool
    {
        return $this->successful;
    }

    public function requestId(): string
    {
        return $this->requestId;
    }

    public function messageId(): ?string
    {
        return $this->messageId;
    }

    public function status(): MessageStatus
    {
        return $this->status;
    }
}