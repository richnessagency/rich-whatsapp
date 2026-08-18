<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Tests\Feature;

use Illuminate\Support\Facades\Event;
use RichnessAgency\RichWhatsApp\Enums\MessageStatus;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageDelivered;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageFailed;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageRead;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageReceived;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageSent;
use RichnessAgency\RichWhatsApp\Events\WhatsAppSessionConnected;
use RichnessAgency\RichWhatsApp\Events\WhatsAppSessionStatusChanged;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppCallbackEvent;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppMessage;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppConversation;
use RichnessAgency\RichWhatsApp\Tests\TestCase;

class CallbackTest extends TestCase
{
    public function test_callback_requires_auth_header(): void
    {
        $response = $this->postJson(route('rich-whatsapp.callback'), [
            'event_id' => 'evt_1234',
            'event_type' => 'session.status',
        ]);

        $response->assertStatus(401);
    }

    public function test_callback_fails_with_invalid_token(): void
    {
        $response = $this->postJson(
            route('rich-whatsapp.callback'),
            [
                'event_id' => 'evt_1234',
                'event_type' => 'session.status',
            ],
            ['Authorization' => 'Bearer invalid-token']
        );

        $response->assertStatus(401);
    }

    public function test_callback_passes_with_valid_token(): void
    {
        $response = $this->postJson(
            route('rich-whatsapp.callback'),
            [
                'event_id' => 'evt_1234',
                'event_type' => 'session.status',
                'status' => 'connected',
            ],
            ['Authorization' => 'Bearer callback-token-for-tests']
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('rich_whatsapp_callback_events', [
            'event_id' => 'evt_1234',
        ]);
    }

    public function test_duplicate_callback_ignored_idempotently(): void
    {
        RichWhatsAppCallbackEvent::create([
            'event_id' => 'evt_dup',
            'event_type' => 'session.status',
        ]);

        Event::fake();

        $response = $this->postJson(
            route('rich-whatsapp.callback'),
            [
                'event_id' => 'evt_dup',
                'event_type' => 'session.status',
                'status' => 'connected',
            ],
            ['Authorization' => 'Bearer callback-token-for-tests']
        );

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Duplicate event ignored.']);
        Event::assertNotDispatched(WhatsAppSessionStatusChanged::class);
    }

    public function test_incoming_message_callback_persists_and_emits_event(): void
    {
        Event::fake([WhatsAppMessageReceived::class]);

        $payload = [
            'event_id' => 'evt_received_1',
            'event_type' => 'message.received',
            'message_id' => 'wa-msg-id-888',
            'from' => '201012345678@s.whatsapp.net',
            'text' => 'Hello from customer',
            'type' => 'text',
            'timestamp' => '2026-08-18T00:00:00Z',
            'is_media' => false,
        ];

        $response = $this->postJson(
            route('rich-whatsapp.callback'),
            $payload,
            ['Authorization' => 'Bearer callback-token-for-tests']
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('rich_whatsapp_conversations', [
            'whatsapp_chat_id' => '201012345678@s.whatsapp.net',
            'unread_count' => 1,
        ]);

        $this->assertDatabaseHas('rich_whatsapp_messages', [
            'whatsapp_message_id' => 'wa-msg-id-888',
            'body' => 'Hello from customer',
            'direction' => 'incoming',
            'status' => 'received',
        ]);

        Event::assertDispatched(WhatsAppMessageReceived::class);
    }

    public function test_message_status_progression_rules_prevent_regression(): void
    {
        $conversation = RichWhatsAppConversation::create([
            'whatsapp_chat_id' => '201012345678@s.whatsapp.net',
        ]);

        $msg = RichWhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'request_id' => 'req-progress',
            'whatsapp_message_id' => 'wa-msg-progress',
            'direction' => 'outgoing',
            'status' => MessageStatus::Read, // Initial status is Read
        ]);

        $payload = [
            'event_id' => 'evt_sent_regression',
            'event_type' => 'message.sent',
            'request_id' => 'req-progress',
            'message_id' => 'wa-msg-progress',
            'status' => 'sent', // Attempting to regression to sent
        ];

        $response = $this->postJson(
            route('rich-whatsapp.callback'),
            $payload,
            ['Authorization' => 'Bearer callback-token-for-tests']
        );

        $response->assertStatus(200);

        // Status should still be read (no regression)
        $this->assertEquals(MessageStatus::Read, $msg->fresh()->status);
    }

    public function test_session_connected_callback_emits_event(): void
    {
        Event::fake([WhatsAppSessionConnected::class, WhatsAppSessionStatusChanged::class]);

        $payload = [
            'event_id' => 'evt_session_connected',
            'event_type' => 'session.status',
            'status' => 'connected',
            'phone' => '201012345678',
        ];

        $response = $this->postJson(
            route('rich-whatsapp.callback'),
            $payload,
            ['Authorization' => 'Bearer callback-token-for-tests']
        );

        $response->assertStatus(200);

        Event::assertDispatched(WhatsAppSessionConnected::class);
        Event::assertDispatched(WhatsAppSessionStatusChanged::class);
    }
}
