<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RichnessAgency\RichWhatsApp\Enums\SessionStatus;

class WhatsAppSessionQrRequired
{
    use Dispatchable;

    public function __construct(
        public SessionStatus $status,
        public ?string $qr = null,
    ) {}
}