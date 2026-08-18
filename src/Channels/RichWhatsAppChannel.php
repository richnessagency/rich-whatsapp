<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Channels;

use Illuminate\Notifications\Notification;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;

class RichWhatsAppChannel
{
    public function __construct(
        protected WhatsApp $service
    ) {}

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return mixed
     */
    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toRichWhatsApp')) {
            return null;
        }

        $message = $notification->toRichWhatsApp($notifiable);

        if (is_string($message)) {
            $message = new RichWhatsAppMessage($message);
        }

        if (! $message instanceof RichWhatsAppMessage) {
            return null;
        }

        $to = $message->phone ?: $notifiable->routeNotificationFor('rich-whatsapp', $notification);

        if (! $to) {
            $to = $notifiable->phone ?? $notifiable->phone_number ?? null;
        }

        if (! $to) {
            return null;
        }

        if ($message->mediaPath) {
            return $this->service->sendMedia(
                phone: (string) $to,
                path: $message->mediaPath,
                type: $message->mediaType,
                filename: $message->mediaFilename,
                mime: $message->mediaMime,
                caption: $message->mediaCaption ?? $message->text,
                idempotencyKey: $message->idempotencyKey
            );
        }

        return $this->service->sendText(
            phone: (string) $to,
            message: $message->text,
            idempotencyKey: $message->idempotencyKey
        );
    }
}
