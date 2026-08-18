<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Services;

use RichnessAgency\RichWhatsApp\Contracts\BridgeClient;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;
use RichnessAgency\RichWhatsApp\Contracts\PendingMessage as PendingMessageContract;
use RichnessAgency\RichWhatsApp\DTOs\HealthReport;
use RichnessAgency\RichWhatsApp\DTOs\MessageResult;
use RichnessAgency\RichWhatsApp\DTOs\QrData;
use RichnessAgency\RichWhatsApp\DTOs\SessionInfo;
use RichnessAgency\RichWhatsApp\DTOs\PagedList;
use RichnessAgency\RichWhatsApp\DTOs\ChatInfo;
use RichnessAgency\RichWhatsApp\DTOs\ContactInfo;
use RichnessAgency\RichWhatsApp\DTOs\ChatMessage;
use RichnessAgency\RichWhatsApp\DTOs\ChatHistory;
use RichnessAgency\RichWhatsApp\Enums\MediaType;
use RichnessAgency\RichWhatsApp\Enums\SessionStatus;
use RichnessAgency\RichWhatsApp\Enums\MessageStatus;
use RichnessAgency\RichWhatsApp\Enums\MessageDirection;
use RichnessAgency\RichWhatsApp\Exceptions\BridgeUnavailableException;
use RichnessAgency\RichWhatsApp\Exceptions\BridgeAuthenticationException;
use RichnessAgency\RichWhatsApp\Exceptions\WhatsAppDisconnectedException;
use RichnessAgency\RichWhatsApp\Exceptions\MessageSendException;
use RichnessAgency\RichWhatsApp\Exceptions\ConfigurationException;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppMessage;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppConversation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsApp
{
    public function __construct(
        protected BridgeClient $client,
        protected PhoneNumberService $phoneService,
        protected MessageService $messageService
    ) {}

    public function enabled(): bool
    {
        return (bool) config('rich-whatsapp.enabled', true);
    }

    public function bridgeConfigured(): bool
    {
        $url = config('rich-whatsapp.bridge_url', '');
        $token = config('rich-whatsapp.bridge_token', '');

        return is_string($url) && $url !== '' && is_string($token) && $token !== '';
    }

    public function to(string $phone): PendingMessageContract
    {
        return new PendingMessage($this, $phone);
    }

    public function sendText(string $phone, string $message, ?string $idempotencyKey = null): MessageResult
    {
        if (! $this->enabled()) {
            return MessageResult::failed($idempotencyKey ?? (string) Str::uuid(), 'Rich WhatsApp is disabled.');
        }

        try {
            $normalizedPhone = $this->phoneService->normalize($phone);
            $requestId = $idempotencyKey ?: (string) Str::uuid();

            // Check if already processed (Idempotency)
            if ($existing = $this->messageService->findByRequestId($requestId)) {
                return new MessageResult(
                    successful: $existing->status !== MessageStatus::Failed,
                    requestId: $existing->request_id,
                    messageId: $existing->whatsapp_message_id,
                    status: $existing->status
                );
            }

            // Create local outgoing record if enabled
            $localMsg = null;
            if (config('rich-whatsapp.store_messages', true) && config('rich-whatsapp.store_outgoing', true)) {
                $localMsg = $this->messageService->createOutgoingText($requestId, $normalizedPhone, $message);
            }

            $response = $this->client->sendText($requestId, $normalizedPhone, $message);
            $result = MessageResult::fromBridge($requestId, $response);

            if ($localMsg && $result->messageId) {
                $localMsg->mark($result->status, now());
                $localMsg->update(['whatsapp_message_id' => $result->messageId]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Rich WhatsApp text send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            $status = MessageStatus::Failed;
            if ($e instanceof WhatsAppDisconnectedException) {
                $status = MessageStatus::Queued;
            }

            if (isset($localMsg) && $localMsg) {
                $localMsg->mark($status, now(), $e->getMessage());
            }

            if ($e instanceof WhatsAppDisconnectedException) {
                // If it is queued on the bridge or queued locally
                return new MessageResult(
                    successful: true,
                    requestId: $requestId ?? (string) Str::uuid(),
                    messageId: null,
                    status: MessageStatus::Queued
                );
            }

            return MessageResult::failed($requestId ?? (string) Str::uuid(), $e->getMessage(), $status);
        }
    }

    public function sendMedia(
        string $phone,
        string $path,
        MediaType $type,
        ?string $filename = null,
        ?string $mime = null,
        ?string $caption = null,
        ?string $idempotencyKey = null
    ): MessageResult {
        if (! $this->enabled()) {
            return MessageResult::failed($idempotencyKey ?? (string) Str::uuid(), 'Rich WhatsApp is disabled.');
        }

        if (! file_exists($path)) {
            return MessageResult::failed($idempotencyKey ?? (string) Str::uuid(), "Media file does not exist: {$path}");
        }

        $maxBytes = config('rich-whatsapp.media_max_mb', 10) * 1024 * 1024;
        if (filesize($path) > $maxBytes) {
            return MessageResult::failed($idempotencyKey ?? (string) Str::uuid(), 'Media file exceeds size limit.');
        }

        try {
            $normalizedPhone = $this->phoneService->normalize($phone);
            $requestId = $idempotencyKey ?: (string) Str::uuid();

            // Check if already processed (Idempotency)
            if ($existing = $this->messageService->findByRequestId($requestId)) {
                return new MessageResult(
                    successful: $existing->status !== MessageStatus::Failed,
                    requestId: $existing->request_id,
                    messageId: $existing->whatsapp_message_id,
                    status: $existing->status
                );
            }

            $resolvedFilename = $filename ?: basename($path);
            $resolvedMime = $mime ?: mime_content_type($path) ?: 'application/octet-stream';

            // Create local outgoing record if enabled
            $localMsg = null;
            if (config('rich-whatsapp.store_messages', true) && config('rich-whatsapp.store_outgoing', true)) {
                $localMsg = $this->messageService->createOutgoingMedia(
                    $requestId,
                    $normalizedPhone,
                    $type,
                    $resolvedFilename,
                    $path,
                    $caption
                );
            }

            $response = $this->client->sendMedia(
                $requestId,
                $normalizedPhone,
                $path,
                $resolvedFilename,
                $resolvedMime,
                $caption
            );

            $result = MessageResult::fromBridge($requestId, $response);

            if ($localMsg && $result->messageId) {
                $localMsg->mark($result->status, now());
                $localMsg->update(['whatsapp_message_id' => $result->messageId]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Rich WhatsApp media send failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            $status = MessageStatus::Failed;
            if ($e instanceof WhatsAppDisconnectedException) {
                $status = MessageStatus::Queued;
            }

            if (isset($localMsg) && $localMsg) {
                $localMsg->mark($status, now(), $e->getMessage());
            }

            if ($e instanceof WhatsAppDisconnectedException) {
                return new MessageResult(
                    successful: true,
                    requestId: $requestId ?? (string) Str::uuid(),
                    messageId: null,
                    status: MessageStatus::Queued
                );
            }

            return MessageResult::failed($requestId ?? (string) Str::uuid(), $e->getMessage(), $status);
        }
    }

    public function sessionStatus(): SessionInfo
    {
        if (! $this->enabled() || ! $this->bridgeConfigured()) {
            return SessionInfo::bridgeOffline();
        }

        try {
            $payload = $this->client->sessionStatus();
            return SessionInfo::fromBridgePayload($payload);
        } catch (\Exception $e) {
            return SessionInfo::bridgeOffline();
        }
    }

    public function startSession(): SessionInfo
    {
        if (! $this->enabled()) {
            return SessionInfo::bridgeOffline();
        }

        try {
            $payload = $this->client->startSession();
            return SessionInfo::fromBridgePayload($payload);
        } catch (\Exception $e) {
            return SessionInfo::bridgeOffline();
        }
    }

    public function qr(): ?QrData
    {
        if (! $this->enabled() || ! $this->bridgeConfigured()) {
            return null;
        }

        try {
            $payload = $this->client->qr();
            $data = $payload['data'] ?? $payload;

            if (isset($data['qr']) && is_string($data['qr'])) {
                return new QrData($data['qr'], $data['expires_at'] ?? null);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function reconnect(): SessionInfo
    {
        if (! $this->enabled()) {
            return SessionInfo::bridgeOffline();
        }

        try {
            $payload = $this->client->reconnect();
            return SessionInfo::fromBridgePayload($payload);
        } catch (\Exception $e) {
            return SessionInfo::bridgeOffline();
        }
    }

    public function logout(): SessionInfo
    {
        if (! $this->enabled()) {
            return SessionInfo::bridgeOffline();
        }

        try {
            $payload = $this->client->logout();
            return SessionInfo::fromBridgePayload($payload);
        } catch (\Exception $e) {
            return SessionInfo::bridgeOffline();
        }
    }

    public function checkContact(string $phone): bool
    {
        if (! $this->enabled() || ! $this->bridgeConfigured()) {
            return false;
        }

        try {
            $normalizedPhone = $this->phoneService->normalize($phone);
            $response = $this->client->checkContact($normalizedPhone);
            $data = $response['data'] ?? $response;

            return (bool) ($data['registered'] ?? false);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function health(): HealthReport
    {
        $enabled = $this->enabled();
        if (! $enabled || ! $this->bridgeConfigured()) {
            return new HealthReport(
                packageEnabled: $enabled,
                bridgeOnline: false,
                bridgeLatencyMs: null,
                sessionStatus: SessionStatus::BridgeOffline,
                phone: null,
                nodeUptime: null,
                queuePending: 0,
                callbackBacklog: 0,
                lastActivityAt: null
            );
        }

        try {
            $start = microtime(true);
            $details = $this->client->healthDetails();
            $latency = (microtime(true) - $start) * 1000;

            $data = $details['data'] ?? $details;
            $whatsapp = $data['whatsapp'] ?? [];

            $status = SessionStatus::fromBridge($whatsapp['status'] ?? null);

            $lastMsg = RichWhatsAppMessage::query()->latest('occurred_at')->first();

            return new HealthReport(
                packageEnabled: true,
                bridgeOnline: true,
                bridgeLatencyMs: round($latency, 2),
                sessionStatus: $status,
                phone: $whatsapp['phone'] ?? null,
                nodeUptime: isset($data['service']['uptime_seconds']) ? $data['service']['uptime_seconds'] . 's' : null,
                queuePending: $data['queue']['pending'] ?? 0,
                callbackBacklog: $data['callbacks']['pending'] ?? 0,
                lastActivityAt: $lastMsg ? $lastMsg->occurred_at?->toIso8601String() : null,
                raw: $data
            );
        } catch (\Exception $e) {
            return new HealthReport(
                packageEnabled: true,
                bridgeOnline: false,
                bridgeLatencyMs: null,
                sessionStatus: SessionStatus::BridgeOffline,
                phone: null,
                nodeUptime: null,
                queuePending: 0,
                callbackBacklog: 0,
                lastActivityAt: null
            );
        }
    }

    // ------------------------------------------------ WhatsApp Web read API

    public function listChats(?string $query = null, ?int $limit = null, ?int $offset = null): PagedList
    {
        if (! $this->enabled() || ! $this->bridgeConfigured()) {
            return PagedList::empty($limit ?? 50, $offset ?? 0);
        }

        try {
            return PagedList::fromBridge(
                $this->client->listChats($query, $limit, $offset),
                static fn (array $row): ChatInfo => ChatInfo::fromBridge($row)
            );
        } catch (\Exception) {
            return PagedList::empty($limit ?? 50, $offset ?? 0);
        }
    }

    public function listContacts(?string $query = null, ?int $limit = null, ?int $offset = null): PagedList
    {
        if (! $this->enabled() || ! $this->bridgeConfigured()) {
            return PagedList::empty($limit ?? 50, $offset ?? 0);
        }

        try {
            return PagedList::fromBridge(
                $this->client->listContacts($query, $limit, $offset),
                static fn (array $row): ContactInfo => ContactInfo::fromBridge($row)
            );
        } catch (\Exception) {
            return PagedList::empty($limit ?? 50, $offset ?? 0);
        }
    }

    public function chatMessages(string $jid, ?int $limit = null, ?string $before = null): ?ChatHistory
    {
        if (! $this->enabled() || ! $this->bridgeConfigured() || $jid === '') {
            return null;
        }

        try {
            return ChatHistory::fromBridge($this->client->chatMessages($jid, $limit, $before));
        } catch (\Exception) {
            return null;
        }
    }

    public function chatMedia(string $jid, string $messageId): ?array
    {
        if (! $this->enabled() || ! $this->bridgeConfigured() || $jid === '' || $messageId === '') {
            return null;
        }

        try {
            return $this->client->chatMedia($jid, $messageId);
        } catch (\Exception) {
            return null;
        }
    }

    public function chatPicture(string $jid): ?array
    {
        if (! $this->enabled() || ! $this->bridgeConfigured() || $jid === '') {
            return null;
        }

        try {
            return $this->client->chatPicture($jid);
        } catch (\Exception) {
            return null;
        }
    }
}
