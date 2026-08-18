<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Enums;

/**
 * Normalized callback event types the Node Bridge may deliver.
 */
enum CallbackEventType: string
{
    case SessionStatus = 'session.status';
    case SessionQr = 'session.qr';
    case MessageReceived = 'message.received';
    case MessageSent = 'message.sent';
    case MessageDelivered = 'message.delivered';
    case MessageRead = 'message.read';
    case MessageFailed = 'message.failed';

    public static function tryFromBridge($value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }
}