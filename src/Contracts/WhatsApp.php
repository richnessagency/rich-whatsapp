<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Contracts;

use RichnessAgency\RichWhatsApp\DTOs\HealthReport;
use RichnessAgency\RichWhatsApp\DTOs\MessageResult;
use RichnessAgency\RichWhatsApp\DTOs\QrData;
use RichnessAgency\RichWhatsApp\DTOs\SessionInfo;
use RichnessAgency\RichWhatsApp\DTOs\PagedList;
use RichnessAgency\RichWhatsApp\DTOs\ChatHistory;
use RichnessAgency\RichWhatsApp\Enums\MediaType;

/**
 * Primary developer-facing API. Implemented by WhatsAppService and proxied by
 * the RichWhatsApp Facade. Can be injected directly thanks to the contract
 * binding registered by the service provider.
 */
interface WhatsApp
{
    public function enabled(): bool;

    public function bridgeConfigured(): bool;

    /** Starts a fluent message builder for a phone number. */
    public function to(string $phone): PendingMessage;

    /** Simple one-shot text send. */
    public function sendText(string $phone, string $message, ?string $idempotencyKey = null): MessageResult;

    /** One-shot media send. */
    public function sendMedia(string $phone, string $path, MediaType $type, ?string $filename = null, ?string $mime = null, ?string $caption = null, ?string $idempotencyKey = null): MessageResult;

    public function sessionStatus(): SessionInfo;

    public function startSession(): SessionInfo;

    public function qr(): ?QrData;

    public function reconnect(): SessionInfo;

    public function logout(): SessionInfo;

    public function checkContact(string $phone): bool;

    public function health(): HealthReport;

    /** WhatsApp Web-style chats list (items are ChatInfo[]). */
    public function listChats(?string $query = null, ?int $limit = null, ?int $offset = null): PagedList;

    /** WhatsApp Web-style contacts list (items are ContactInfo[]). */
    public function listContacts(?string $query = null, ?int $limit = null, ?int $offset = null): PagedList;

    /** Message history for a single chat, oldest → newest. */
    public function chatMessages(string $jid, ?int $limit = null, ?string $before = null): ?ChatHistory;

    /**
     * Downloads a media message body through the bridge.
     *
     * @return array{body: string, content_type: string|null, filename: string|null}|null
     */
    public function chatMedia(string $jid, string $messageId): ?array;

    /**
     * Downloads a chat profile picture through the bridge.
     *
     * @return array{body: string, content_type: string|null, filename: string|null}|null
     */
    public function chatPicture(string $jid): ?array;
}