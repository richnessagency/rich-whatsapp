<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Models;

use Illuminate\Database\Eloquent\Model;

class RichWhatsAppCallbackEvent extends Model
{
    protected $table = 'rich_whatsapp_callback_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /** Looks up an already-processed event_id (idempotency helper). */
    public static function alreadyProcessed(string $eventId): bool
    {
        return static::query()->whereKey($eventId)->exists()
            || static::query()->where('event_id', $eventId)->exists();
    }

    public static function recordProcessed(array $data): self
    {
        return static::query()->updateOrCreate(
            ['event_id' => $data['event_id']],
            [
                'event_type' => $data['event_type'],
                'payload' => $data['payload'] ?? null,
                'message_phone' => $data['message_phone'] ?? null,
            ]
        );
    }
}