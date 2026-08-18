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
}
