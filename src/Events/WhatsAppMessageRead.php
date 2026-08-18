<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppMessage;

class WhatsAppMessageRead
{
    use Dispatchable;

    public function __construct(
        public RichWhatsAppMessage $message,
        public string $requestId,
    ) {}
}