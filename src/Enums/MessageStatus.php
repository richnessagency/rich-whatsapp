<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Enums;

/**
 * Normalized delivery lifecycle of an outbound message.
 *
 * The bridge (Node) is the source of truth. The package never invents statuses
 * the bridge did not report. The progression policy is strict:
 * queued < submitted < sent < delivered < read, with failed reachable.
 */
enum MessageStatus: string
{
    case Queued = 'queued';
    case Submitted = 'submitted';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Received = 'received';

    private const ORDER = [
        'submitted' => 1,
        'queued' => 1,
        'sent' => 2,
        'delivered' => 3,
        'read' => 4,
        'failed' => 5,
        'received' => 1,
    ];

    public function rank(): int
    {
        return self::ORDER[$this->value] ?? 0;
    }

    public static function tryFromBridge($value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return self::tryFrom(strtolower($value));
    }

    /**
     * Returns true when moving from $current to $next never regresses the rank.
     * 'queued' and 'submitted' share a rank; equal ranks are allowed.
     */
    public static function isProgression(?self $current, ?self $next): bool
    {
        if ($next === null || $next === self::Received) {
            return false;
        }

        // A message that was already delivered/read cannot go backwards.
        return $current === null || $next->rank() >= $current->rank();
    }
}