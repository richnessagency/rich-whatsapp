<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Enums;

enum MessageDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}