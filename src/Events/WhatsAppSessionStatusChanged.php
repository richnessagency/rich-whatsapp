<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;

class WhatsAppSessionStatusChanged
{
    use Dispatchable;

    public function __construct(
        public ?string $previous,
        public string $current,
        public ?string $phone = null,
    ) {}
}