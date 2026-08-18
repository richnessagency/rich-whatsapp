<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

/** Immutable view of a chat (conversation) as reported by the bridge. */
final readonly class ChatInfo
{
    public function __construct(
        public string $jid,
        public bool $isGroup,
        public string $name,
        public int $unreadCount,
        public ?array $lastMessage,
        public ?string $lastMessageAt,
    ) {}

    public static function fromBridge(array $data): self
    {
        $last = isset($data['last_message']) && is_array($data['last_message'])
            ? $data['last_message']
            : null;

        return new self(
            jid: (string) ($data['jid'] ?? ''),
            isGroup: (bool) ($data['is_group'] ?? false),
            name: (string) ($data['name'] ?? 'Unknown'),
            unreadCount: (int) ($data['unread_count'] ?? 0),
            lastMessage: $last,
            lastMessageAt: isset($data['last_message_at']) && is_string($data['last_message_at'])
                ? $data['last_message_at']
                : null,
        );
    }

    /** Phone number for non-group chats (used for composing new messages). */
    public function phone(): ?string
    {
        if ($this->isGroup) {
            return null;
        }

        return explode('@', $this->jid, 2)[0] ?: null;
    }
}