<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RichWhatsAppConversation extends Model
{
    protected $table = 'rich_whatsapp_conversations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'unread_count' => 'integer',
            'is_archived' => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RichWhatsAppMessage::class, 'conversation_id');
    }

    public function markRead(): static
    {
        if ($this->unread_count > 0) {
            $this->update(['unread_count' => 0]);
        }

        return $this;
    }
}