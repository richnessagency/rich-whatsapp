<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Tests\Feature;

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use RichnessAgency\RichWhatsApp\Channels\RichWhatsAppChannel;
use RichnessAgency\RichWhatsApp\Channels\RichWhatsAppMessage;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;
use RichnessAgency\RichWhatsApp\DTOs\MessageResult;
use RichnessAgency\RichWhatsApp\Enums\MediaType;
use RichnessAgency\RichWhatsApp\Enums\MessageStatus;
use RichnessAgency\RichWhatsApp\Tests\TestCase;
use Mockery;
use Mockery\MockInterface;

class NotificationChannelTest extends TestCase
{
    protected MockInterface $whatsappMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->whatsappMock = Mockery::mock(WhatsApp::class);
        $this->app->instance(WhatsApp::class, $this->whatsappMock);
    }

    public function test_channel_sends_notification_successfully(): void
    {
        $notifiable = new class {
            use Notifiable;
            
            public string $phone = '201012345678';
            
            public function routeNotificationForRichWhatsapp(): string
            {
                return $this->phone;
            }
        };

        $notification = new class extends Notification {
            public function via($notifiable): array
            {
                return ['rich-whatsapp'];
            }
            
            public function toRichWhatsApp($notifiable): RichWhatsAppMessage
            {
                return RichWhatsAppMessage::create('Order is ready!')
                    ->idempotencyKey('order-ready-123');
            }
        };

        $this->whatsappMock->shouldReceive('sendText')
            ->once()
            ->with('201012345678', 'Order is ready!', 'order-ready-123')
            ->andReturn(new MessageResult(true, 'order-ready-123', 'wa-123', MessageStatus::Submitted));

        $notifiable->notify($notification);
    }
}
