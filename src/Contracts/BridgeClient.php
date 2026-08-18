<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Contracts;

/**
 * All HTTP communication with the Node Bridge happens through this contract,
 * implemented by WhatsAppBridgeClient. Controllers/services never call
 * Http::() directly, keeping the bridge protocol adaptable in one place.
 */
interface BridgeClient
{
    public function health(): array;

    public function healthDetails(): array;

    public function startSession(): array;

    public function sessionStatus(): array;

    public function qr(): array;

    public function reconnect(): array;

    public function logout(): array;

    public function sendText(string $requestId, string $phone, string $message): array;

    public function sendMedia(string $requestId, string $phone, string $path, string $filename, string $mime, ?string $caption = null): array;

    public function messageStatus(string $requestId): array;

    public function checkContact(string $phone): array;

    public function listContacts(?string $query = null, ?int $limit = null, ?int $offset = null): array;

    public function listChats(?string $query = null, ?int $limit = null, ?int $offset = null): array;

    public function chatMessages(string $jid, ?int $limit = null, ?string $before = null): array;

    /**
     * Downloads a media message body from the bridge.
     *
     * @return array{body: string, content_type: string|null, filename: string|null}
     */
    public function chatMedia(string $jid, string $messageId): array;

    /**
     * Downloads a chat profile picture from the bridge.
     *
     * @return array{body: string, content_type: string|null, filename: string|null}
     */
    public function chatPicture(string $jid): array;
}