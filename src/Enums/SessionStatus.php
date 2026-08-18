<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Enums;

/**
 * WhatsApp connection status as reported by the Node Bridge.
 * Mirrors the normalized statuses emitted by the bridge, plus an explicit
 * 'bridge_offline' value the Laravel package adds when the bridge is unreachable.
 */
enum SessionStatus: string
{
    case Initializing = 'initializing';
    case Connecting = 'connecting';
    case QrRequired = 'qr_required';
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Reconnecting = 'reconnecting';
    case AuthFailed = 'auth_failed';
    case LoggedOut = 'logged_out';
    case Error = 'error';
    case BridgeOffline = 'bridge_offline';
    case Unknown = 'unknown';

    public static function fromBridge(?string $value): self
    {
        return self::tryFrom(is_string($value) ? strtolower($value) : '') ?? self::Unknown;
    }

    public function label(): string
    {
        return match ($this) {
            self::Initializing => 'Initializing',
            self::Connecting => 'Connecting',
            self::QrRequired => 'QR Required',
            self::Connected => 'Connected',
            self::Disconnected => 'Disconnected',
            self::Reconnecting => 'Reconnecting',
            self::AuthFailed => 'Authentication Failed',
            self::LoggedOut => 'Logged Out',
            self::Error => 'Error',
            self::BridgeOffline => 'Node Bridge Offline',
            self::Unknown => 'Unknown',
        };
    }

    public function isConnected(): bool
    {
        return $this === self::Connected;
    }
}