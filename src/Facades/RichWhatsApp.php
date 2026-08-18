<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Facades;

use Illuminate\Support\Facades\Facade;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

/**
 * @method static bool enabled()
 * @method static bool bridgeConfigured()
 * @method static \RichnessAgency\RichWhatsApp\Contracts\PendingMessage to(string $phone)
 * @method static \RichnessAgency\RichWhatsApp\DTOs\MessageResult sendText(string $phone, string $message, ?string $idempotencyKey = null)
 * @method static \RichnessAgency\RichWhatsApp\DTOs\MessageResult sendMedia(string $phone, string $path, \RichnessAgency\RichWhatsApp\Enums\MediaType $type, ?string $filename = null, ?string $mime = null, ?string $caption = null, ?string $idempotencyKey = null)
 * @method static \RichnessAgency\RichWhatsApp\DTOs\SessionInfo sessionStatus()
 * @method static \RichnessAgency\RichWhatsApp\DTOs\SessionInfo startSession()
 * @method static \RichnessAgency\RichWhatsApp\DTOs\QrData|null qr()
 * @method static \RichnessAgency\RichWhatsApp\DTOs\SessionInfo reconnect()
 * @method static \RichnessAgency\RichWhatsApp\DTOs\SessionInfo logout()
 * @method static bool checkContact(string $phone)
 * @method static \RichnessAgency\RichWhatsApp\DTOs\HealthReport health()
 *
 * @see \RichnessAgency\RichWhatsApp\Services\WhatsAppService
 */
class RichWhatsApp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return WhatsApp::class;
    }
}
