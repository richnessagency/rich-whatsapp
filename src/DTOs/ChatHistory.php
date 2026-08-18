<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

/**
 * One page of chat message history. Messages are always returned oldest →
 * newest so the UI can append them above the currently displayed thread.
 */
final readonly class ChatHistory
{
    /**
     * @param  array<ChatMessage>  $messages
     */
    public function __construct(
        public string $jid,
        public array $messages,
        public bool $hasMore,
        public ?string $nextCursor,
        public int $total,
    ) {}

    public static function fromBridge(array $payload): self
    {
        $data = $payload['data'] ?? $payload;

        $messages = [];
        foreach (($data['messages'] ?? []) as $row) {
            $messages[] = ChatMessage::fromBridge($row);
        }

        return new self(
            jid: (string) ($data['jid'] ?? ''),
            messages: $messages,
            hasMore: (bool) ($data['has_more'] ?? false),
            nextCursor: isset($data['next_cursor']) ? (string) $data['next_cursor'] : null,
            total: (int) ($data['total'] ?? count($messages)),
        );
    }

    public static function empty(string $jid): self
    {
        return new self($jid, [], false, null, 0);
    }
}