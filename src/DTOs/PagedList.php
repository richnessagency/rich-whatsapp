<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

/**
 * Generic paged result for bridge list endpoints (chats/contacts).
 *
 * @template T
 */
final readonly class PagedList
{
    /**
     * @param  array<T>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $limit,
        public int $offset,
    ) {}

    /**
     * @return PagedList<T>
     */
    public static function empty(int $limit = 50, int $offset = 0): self
    {
        return new self([], 0, $limit, $offset);
    }

    public function hasMore(): bool
    {
        return $this->offset + count($this->items) < $this->total;
    }

    public function nextOffset(): int
    {
        return $this->offset + count($this->items);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(array): T  $map
     *
     * @return PagedList<T>
     */
    public static function fromBridge(array $payload, callable $map): self
    {
        $data = $payload['data'] ?? $payload;
        $items = [];
        foreach (($data['items'] ?? []) as $row) {
            $items[] = $map($row);
        }

        return new self(
            items: $items,
            total: (int) ($data['total'] ?? count($items)),
            limit: (int) ($data['limit'] ?? 50),
            offset: (int) ($data['offset'] ?? 0),
        );
    }
}