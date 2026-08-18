<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Tests\Feature;

use RichnessAgency\RichWhatsApp\Contracts\BridgeClient;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;
use RichnessAgency\RichWhatsApp\DTOs\MessageResult;
use RichnessAgency\RichWhatsApp\Enums\MediaType;
use RichnessAgency\RichWhatsApp\Enums\MessageStatus;
use RichnessAgency\RichWhatsApp\Enums\SessionStatus;
use RichnessAgency\RichWhatsApp\Exceptions\BridgeAuthenticationException;
use RichnessAgency\RichWhatsApp\Exceptions\BridgeUnavailableException;
use RichnessAgency\RichWhatsApp\Exceptions\WhatsAppDisconnectedException;
use RichnessAgency\RichWhatsApp\Exceptions\ConfigurationException;
use RichnessAgency\RichWhatsApp\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Support\Str;

class WhatsAppServiceTest extends TestCase
{
    protected WhatsApp $service;
    protected MockInterface $clientMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->clientMock = Mockery::mock(BridgeClient::class);
        $this->app->instance(BridgeClient::class, $this->clientMock);
        
        $this->service = $this->app->make(WhatsApp::class);
    }

    public function test_package_boots_and_service_resolves(): void
    {
        $this->assertInstanceOf(WhatsApp::class, $this->service);
        $this->assertTrue($this->service->enabled());
        $this->assertTrue($this->service->bridgeConfigured());
    }

    public function test_send_text_calls_bridge_client_and_persists_outgoing(): void
    {
        $phone = '201012345678';
        $message = 'Hello world';
        $requestId = (string) Str::uuid();

        $this->clientMock->shouldReceive('sendText')
            ->once()
            ->with($requestId, $phone, $message)
            ->andReturn([
                'success' => true,
                'request_id' => $requestId,
                'message_id' => 'msg-wa-1234',
                'status' => 'submitted',
            ]);

        $result = $this->service->sendText($phone, $message, $requestId);

        $this->assertInstanceOf(MessageResult::class, $result);
        $this->assertTrue($result->successful());
        $this->assertEquals($requestId, $result->requestId);
        $this->assertEquals('msg-wa-1234', $result->messageId);
        $this->assertEquals(MessageStatus::Submitted, $result->status);

        $this->assertDatabaseHas('rich_whatsapp_messages', [
            'request_id' => $requestId,
            'whatsapp_message_id' => 'msg-wa-1234',
            'body' => $message,
            'status' => 'submitted',
        ]);
    }

    public function test_idempotency_prevents_duplicate_sending(): void
    {
        $phone = '201012345678';
        $message = 'Hello world';
        $requestId = (string) Str::uuid();

        $this->clientMock->shouldReceive('sendText')
            ->once()
            ->with($requestId, $phone, $message)
            ->andReturn([
                'success' => true,
                'request_id' => $requestId,
                'message_id' => 'msg-wa-1234',
                'status' => 'submitted',
            ]);

        $res1 = $this->service->sendText($phone, $message, $requestId);
        $res2 = $this->service->sendText($phone, $message, $requestId);

        $this->assertTrue($res1->successful());
        $this->assertTrue($res2->successful());
        $this->assertEquals($res1->messageId, $res2->messageId);
    }

    public function test_disconnected_whatsapp_queues_message_if_bridge_throws(): void
    {
        $phone = '201012345678';
        $message = 'Hello world';
        $requestId = (string) Str::uuid();

        $this->clientMock->shouldReceive('sendText')
            ->once()
            ->andThrow(new WhatsAppDisconnectedException());

        $result = $this->service->sendText($phone, $message, $requestId);

        $this->assertTrue($result->successful());
        $this->assertEquals(MessageStatus::Queued, $result->status);

        $this->assertDatabaseHas('rich_whatsapp_messages', [
            'request_id' => $requestId,
            'status' => 'queued',
        ]);
    }

    public function test_handles_offline_bridge_gracefully(): void
    {
        $phone = '201012345678';
        $message = 'Hello world';
        $requestId = (string) Str::uuid();

        $this->clientMock->shouldReceive('sendText')
            ->once()
            ->andThrow(new BridgeUnavailableException('Bridge is offline'));

        $result = $this->service->sendText($phone, $message, $requestId);

        $this->assertFalse($result->successful());
        $this->assertEquals(MessageStatus::Failed, $result->status);
    }

    public function test_session_status_parses_status(): void
    {
        $this->clientMock->shouldReceive('sessionStatus')
            ->once()
            ->andReturn([
                'success' => true,
                'status' => 'connected',
                'phone' => '201012345678',
                'has_credentials' => true,
            ]);

        $session = $this->service->sessionStatus();

        $this->assertTrue($session->bridgeOnline);
        $this->assertEquals(SessionStatus::Connected, $session->status);
        $this->assertEquals('201012345678', $session->phone);
    }

    public function test_qr_returns_qr_data(): void
    {
        $this->clientMock->shouldReceive('qr')
            ->once()
            ->andReturn([
                'success' => true,
                'qr' => 'data:image/png;base64,12345',
                'expires_at' => '2026-08-18T00:00:00Z',
            ]);

        $qr = $this->service->qr();

        $this->assertNotNull($qr);
        $this->assertEquals('data:image/png;base64,12345', $qr->qr);
        $this->assertTrue($qr->isDataUrl());
    }

    public function test_check_contact_returns_boolean(): void
    {
        $this->clientMock->shouldReceive('checkContact')
            ->once()
            ->with('201012345678')
            ->andReturn([
                'success' => true,
                'data' => [
                    'registered' => true,
                ],
            ]);

        $registered = $this->service->checkContact('201012345678');
        $this->assertTrue($registered);
    }

    public function test_list_chats_maps_bridge_payload_to_chat_info(): void
    {
        $this->clientMock->shouldReceive('listChats')
            ->once()
            ->with(null, 100, 0)
            ->andReturn([
                'success' => true,
                'data' => [
                    'items' => [
                        [
                            'jid' => '201012345678@s.whatsapp.net',
                            'is_group' => false,
                            'name' => 'Ahmed',
                            'unread_count' => 2,
                            'last_message' => ['id' => 'a1', 'from_me' => false, 'text' => 'hi'],
                            'last_message_at' => '2026-08-18T01:00:00Z',
                        ],
                    ],
                    'total' => 1,
                    'limit' => 100,
                    'offset' => 0,
                ],
            ]);

        $page = $this->service->listChats(null, 100, 0);

        $this->assertEquals(1, $page->total);
        $this->assertCount(1, $page->items);
        $this->assertEquals('Ahmed', $page->items[0]->name);
        $this->assertEquals('201012345678', $page->items[0]->phone());
        $this->assertEquals(2, $page->items[0]->unreadCount);
    }

    public function test_list_chats_returns_empty_when_bridge_offline(): void
    {
        $this->clientMock->shouldReceive('listChats')
            ->once()
            ->andThrow(new BridgeUnavailableException('Bridge is offline'));

        $page = $this->service->listChats();

        $this->assertEquals(0, $page->total);
        $this->assertEmpty($page->items);
    }

    public function test_list_contacts_maps_bridge_payload_to_contact_info(): void
    {
        $this->clientMock->shouldReceive('listContacts')
            ->once()
            ->with('sara', 200, 0)
            ->andReturn([
                'success' => true,
                'data' => [
                    'items' => [
                        [
                            'jid' => '201098765432@s.whatsapp.net',
                            'lid' => null,
                            'name' => 'Sara',
                            'notify' => 'Sara',
                            'verified_name' => null,
                            'status' => 'Hey there!',
                            'phone' => '201098765432',
                        ],
                    ],
                    'total' => 1,
                    'limit' => 200,
                    'offset' => 0,
                ],
            ]);

        $page = $this->service->listContacts('sara', 200, 0);

        $this->assertEquals(1, $page->total);
        $this->assertEquals('Sara', $page->items[0]->name);
        $this->assertEquals('201098765432', $page->items[0]->phone);
        $this->assertEquals('Hey there!', $page->items[0]->status);
    }

    public function test_chat_messages_maps_bridge_history_to_dtos(): void
    {
        $this->clientMock->shouldReceive('chatMessages')
            ->once()
            ->with('201012345678@s.whatsapp.net', 50, null)
            ->andReturn([
                'success' => true,
                'data' => [
                    'jid' => '201012345678@s.whatsapp.net',
                    'messages' => [
                        [
                            'id' => 'wa-1',
                            'from_me' => false,
                            'from' => '201012345678@s.whatsapp.net',
                            'participant' => null,
                            'timestamp' => '2026-08-18T01:00:00Z',
                            'type' => 'text',
                            'text' => 'Hello!',
                            'is_media' => false,
                        ],
                        [
                            'id' => 'wa-2',
                            'from_me' => false,
                            'from' => '201012345678@s.whatsapp.net',
                            'participant' => null,
                            'timestamp' => '2026-08-18T01:01:00Z',
                            'type' => 'image',
                            'text' => null,
                            'caption' => 'selfie',
                            'mimetype' => 'image/jpeg',
                            'filename' => null,
                            'duration' => null,
                            'latitude' => null,
                            'longitude' => null,
                            'contact_name' => null,
                            'is_media' => true,
                        ],
                    ],
                    'has_more' => false,
                    'next_cursor' => null,
                    'total' => 2,
                ],
            ]);

        $history = $this->service->chatMessages('201012345678@s.whatsapp.net', 50);

        $this->assertNotNull($history);
        $this->assertEquals(2, $history->total);
        $this->assertCount(2, $history->messages);
        $this->assertFalse($history->messages[0]->isMedia);
        $this->assertEquals('Hello!', $history->messages[0]->displayText());
        $this->assertTrue($history->messages[1]->isMedia);
        $this->assertEquals('image', $history->messages[1]->type);
        $this->assertFalse($history->hasMore);
        $this->assertStringContainsString('Image', $history->messages[1]->displayText());
    }

    public function test_chat_media_returns_binary_payload(): void
    {
        $this->clientMock->shouldReceive('chatMedia')
            ->once()
            ->with('201012345678@s.whatsapp.net', 'wa-2')
            ->andReturn([
                'body' => 'actual-bytes',
                'content_type' => 'image/jpeg',
                'filename' => 'photo.jpg',
            ]);

        $media = $this->service->chatMedia('201012345678@s.whatsapp.net', 'wa-2');

        $this->assertNotNull($media);
        $this->assertEquals('actual-bytes', $media['body']);
        $this->assertEquals('image/jpeg', $media['content_type']);
    }

    public function test_chat_media_returns_null_when_unavailable(): void
    {
        $this->clientMock->shouldReceive('chatMedia')
            ->once()
            ->andThrow(new WhatsAppDisconnectedException());

        $this->assertNull($this->service->chatMedia('201012345678@s.whatsapp.net', 'wa-2'));
    }

    public function test_chat_picture_returns_binary_payload(): void
    {
        $this->clientMock->shouldReceive('chatPicture')
            ->once()
            ->with('201012345678@s.whatsapp.net')
            ->andReturn([
                'body' => 'avatar',
                'content_type' => 'image/jpeg',
                'filename' => null,
            ]);

        $picture = $this->service->chatPicture('201012345678@s.whatsapp.net');

        $this->assertNotNull($picture);
        $this->assertEquals('avatar', $picture['body']);
        $this->assertEquals('image/jpeg', $picture['content_type']);
    }
}
