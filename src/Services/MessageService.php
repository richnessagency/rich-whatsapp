<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Services;

use RichnessAgency\RichWhatsApp\Enums\MessageDirection;
use RichnessAgency\RichWhatsApp\Enums\MessageStatus;
use RichnessAgency\RichWhatsApp\Enums\MediaType;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppConversation;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppMessage;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function findByRequestId(string $requestId): ?RichWhatsAppMessage
    {
        return RichWhatsAppMessage::query()->where('request_id', $requestId)->first();
    }

    public function findByWhatsAppMessageId(string $whatsappMessageId): ?RichWhatsAppMessage
    {
        return RichWhatsAppMessage::query()->where('whatsapp_message_id', $whatsappMessageId)->first();
    }

    public function createOutgoingText(string $requestId, string $phone, string $message): RichWhatsAppMessage
    {
        return DB::transaction(function () use ($requestId, $phone, $message) {
            $conversation = $this->getOrCreateConversation($phone);

            $msg = RichWhatsAppMessage::create([
                'conversation_id' => $conversation->id,
                'request_id' => $requestId,
                'direction' => MessageDirection::Outgoing,
                'status' => MessageStatus::Submitted,
                'to_phone' => $phone,
                'body' => $message,
                'occurred_at' => now(),
            ]);

            $conversation->update([
                'last_message_preview' => Str_limit($message, 100),
                'last_message_at' => $msg->occurred_at,
                'last_message_direction' => MessageDirection::Outgoing->value,
            ]);

            return $msg;
        });
    }

    public function createOutgoingMedia(
        string $requestId,
        string $phone,
        MediaType $type,
        string $filename,
        string $path,
        ?string $caption = null
    ): RichWhatsAppMessage {
        return DB::transaction(function () use ($requestId, $phone, $type, $filename, $path, $caption) {
            $conversation = $this->getOrCreateConversation($phone);
            $preview = '[' . ucfirst($type->value) . '] ' . ($caption ?: $filename);

            $msg = RichWhatsAppMessage::create([
                'conversation_id' => $conversation->id,
                'request_id' => $requestId,
                'direction' => MessageDirection::Outgoing,
                'status' => MessageStatus::Submitted,
                'to_phone' => $phone,
                'body' => $caption,
                'media_type' => $type->value,
                'media_name' => $filename,
                'media_path_or_reference' => $path,
                'occurred_at' => now(),
            ]);

            $conversation->update([
                'last_message_preview' => Str_limit($preview, 100),
                'last_message_at' => $msg->occurred_at,
                'last_message_direction' => MessageDirection::Outgoing->value,
            ]);

            return $msg;
        });
    }

    public function handleIncomingMessage(array $payload): RichWhatsAppMessage
    {
        return DB::transaction(function () use ($payload) {
            $fromJid = $payload['from'] ?? '';
            $phone = explode('@', $fromJid)[0];
            $whatsappMessageId = $payload['message_id'] ?? '';
            $text = $payload['text'] ?? '';
            $type = $payload['type'] ?? 'text';
            $isMedia = (bool) ($payload['is_media'] ?? false);

            $conversation = $this->getOrCreateConversation($phone);

            // Node retries callback events; protect against duplicates
            if ($existing = $this->findByWhatsAppMessageId($whatsappMessageId)) {
                return $existing;
            }

            $mediaType = $isMedia ? $type : null;
            $mediaName = $isMedia ? ($payload['filename'] ?? 'media') : null;
            $mediaPath = $isMedia ? ($payload['media_url'] ?? null) : null;

            $preview = $text;
            if ($isMedia) {
                $preview = '[' . ucfirst($type) . '] ' . ($text ?: $mediaName);
            }

            $msg = RichWhatsAppMessage::create([
                'conversation_id' => $conversation->id,
                'whatsapp_message_id' => $whatsappMessageId,
                'direction' => MessageDirection::Incoming,
                'status' => MessageStatus::Received,
                'from_phone' => $phone,
                'body' => $text,
                'media_type' => $mediaType,
                'media_name' => $mediaName,
                'media_path_or_reference' => $mediaPath,
                'occurred_at' => isset($payload['timestamp']) ? new \DateTime($payload['timestamp']) : now(),
            ]);

            $conversation->update([
                'last_message_preview' => Str_limit($preview, 100),
                'last_message_at' => $msg->occurred_at,
                'last_message_direction' => MessageDirection::Incoming->value,
                'unread_count' => $conversation->unread_count + 1,
            ]);

            return $msg;
        });
    }

    public function getOrCreateConversation(string $phone): RichWhatsAppConversation
    {
        $chatId = $phone . '@s.whatsapp.net';

        return RichWhatsAppConversation::query()->updateOrCreate(
            ['whatsapp_chat_id' => $chatId],
            [
                'phone' => $phone,
                'display_name' => '+' . $phone,
            ]
        );
    }
}

// Global helper fallback or Str wrapper to avoid dependency issues on older laravel
if (! function_exists('\RichnessAgency\RichWhatsApp\Services\Str_limit')) {
    function Str_limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }
}
