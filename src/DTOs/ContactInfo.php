<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\DTOs;

/** Immutable view of a WhatsApp contact as reported by the bridge. */
final readonly class ContactInfo
{
    public function __construct(
        public string $jid,
        public ?string $lid,
        public string $name,
        public ?string $notify,
        public ?string $verifiedName,
        public ?string $status,
        public ?string $phone,
    ) {}

    public static function fromBridge(array $data): self
    {
        return new self(
            jid: (string) ($data['jid'] ?? ''),
            lid: isset($data['lid']) ? (string) $data['lid'] : null,
            name: (string) ($data['name'] ?? 'Unknown'),
            notify: isset($data['notify']) ? (string) $data['notify'] : null,
            verifiedName: isset($data['verified_name']) ? (string) $data['verified_name'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
        );
    }
}