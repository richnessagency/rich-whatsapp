<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RichnessAgency\RichWhatsApp\Enums\MessageDirection;
use RichnessAgency\RichWhatsApp\Enums\MessageStatus;

class RichWhatsAppMessage extends Model
{
    protected $table = 'rich_whatsapp_messages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'status' => MessageStatus::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(RichWhatsAppConversation::class, 'conversation_id');
    }

    public function mark(MessageStatus $status, ?\DateTimeInterface $at = null, ?string $reason = null): bool
    {
        if (! MessageStatus::isProgression($this->status, $status)) {
            return false;
        }

        $at ??= now();

        $data = ['status' => $status, 'failure_reason' => $status === MessageStatus::Failed ? $reason : null];

        if ($status === MessageStatus::Sent) {
            $data['sent_at'] = $at;
        } elseif ($status === MessageStatus::Delivered && $this->sent_at === null) {
            $data['sent_at'] = $at;
            $data['delivered_at'] = $at;
        } elseif ($status === MessageStatus::Delivered) {
            $data['delivered_at'] = $at;
        } elseif ($status === MessageStatus::Read) {
            if ($this->delivered_at === null) {
                $data['delivered_at'] = $at;
            }

            $data['read_at'] = $at;
        } elseif ($status === MessageStatus::Failed) {
            $data['failed_at'] = $at;
        }

        $this->forceFill($data)->save();

        return true;
    }
}