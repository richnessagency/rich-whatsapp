<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RichnessAgency\RichWhatsApp\DTOs\MessageResult;

class WhatsAppMessageSubmitted
{
    use Dispatchable;

    public function __construct(
        public MessageResult $result,
        public array $meta = [],
    ) {}
}