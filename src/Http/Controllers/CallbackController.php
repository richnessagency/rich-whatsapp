<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RichnessAgency\RichWhatsApp\Enums\CallbackEventType;
use RichnessAgency\RichWhatsApp\Enums\MessageStatus;
use RichnessAgency\RichWhatsApp\Enums\SessionStatus;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageDelivered;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageFailed;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageRead;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageReceived;
use RichnessAgency\RichWhatsApp\Events\WhatsAppMessageSent;
use RichnessAgency\RichWhatsApp\Events\WhatsAppSessionConnected;
use RichnessAgency\RichWhatsApp\Events\WhatsAppSessionDisconnected;
use RichnessAgency\RichWhatsApp\Events\WhatsAppSessionQrRequired;
use RichnessAgency\RichWhatsApp\Events\WhatsAppSessionStatusChanged;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppCallbackEvent;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppMessage;
use RichnessAgency\RichWhatsApp\Services\MessageService;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    public function handle(Request $request)
    {
        $payload = $request->all();

        $eventId = $payload['event_id'] ?? null;
        $eventTypeRaw = $payload['event_type'] ?? null;

        if (! $eventId || ! $eventTypeRaw) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_REQUEST', 'message' => 'Missing event_id or event_type.'],
            ], 400);
        }

        $eventType = CallbackEventType::tryFromBridge($eventTypeRaw);

        if (! $eventType) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'INVALID_REQUEST', 'message' => "Unsupported event_type: {$eventTypeRaw}."],
            ], 400);
        }

        // Idempotency check
        if (RichWhatsAppCallbackEvent::alreadyProcessed($eventId)) {
            return response()->json([
                'success' => true,
                'message' => 'Duplicate event ignored.',
            ]);
        }

        try {
            $phone = null;

            switch ($eventType) {
                case CallbackEventType::SessionStatus:
                    $statusRaw = $payload['status'] ?? '';
                    $sessionStatus = SessionStatus::fromBridge($statusRaw);
                    $phone = $payload['phone'] ?? null;

                    // Fetch previous status from DB last event or config to dispatch changed event
                    $lastEvent = RichWhatsAppCallbackEvent::query()
                        ->where('event_type', CallbackEventType::SessionStatus->value)
                        ->latest()
                        ->first();
                    $previousStatus = $lastEvent ? ($lastEvent->payload['status'] ?? null) : null;

                    event(new WhatsAppSessionStatusChanged($previousStatus, $statusRaw, $phone));

                    if ($sessionStatus->isConnected()) {
                        event(new WhatsAppSessionConnected($sessionStatus, $phone));
                    } elseif ($sessionStatus === SessionStatus::Disconnected) {
                        event(new WhatsAppSessionDisconnected($sessionStatus, $payload['reason'] ?? null));
                    } elseif ($sessionStatus === SessionStatus::QrRequired) {
                        event(new WhatsAppSessionQrRequired($sessionStatus, $payload['qr'] ?? null));
                    }
                    break;

                case CallbackEventType::SessionQr:
                    $statusRaw = 'qr_required';
                    $sessionStatus = SessionStatus::QrRequired;
                    $qr = $payload['qr'] ?? null;

                    $lastEvent = RichWhatsAppCallbackEvent::query()
                        ->where('event_type', CallbackEventType::SessionStatus->value)
                        ->latest()
                        ->first();
                    $previousStatus = $lastEvent ? ($lastEvent->payload['status'] ?? null) : null;

                    event(new WhatsAppSessionStatusChanged($previousStatus, $statusRaw, null));
                    event(new WhatsAppSessionQrRequired($sessionStatus, $qr));
                    break;

                case CallbackEventType::MessageReceived:
                    $msg = $this->messageService->handleIncomingMessage($payload);
                    $phone = $msg->from_phone;

                    event(new WhatsAppMessageReceived($msg, $msg->conversation, $payload));
                    break;

                case CallbackEventType::MessageSent:
                case CallbackEventType::MessageDelivered:
                case CallbackEventType::MessageRead:
                case CallbackEventType::MessageFailed:
                    $requestId = $payload['request_id'] ?? null;
                    $whatsappMessageId = $payload['message_id'] ?? null;
                    $statusRaw = $payload['status'] ?? null;

                    $msgStatus = MessageStatus::tryFromBridge($statusRaw);

                    if ($msgStatus && $requestId) {
                        $msg = $this->messageService->findByRequestId($requestId);
                        if (! $msg && $whatsappMessageId) {
                            $msg = $this->messageService->findByWhatsAppMessageId($whatsappMessageId);
                        }

                        if ($msg) {
                            $phone = $msg->to_phone ?: $msg->from_phone;

                            // Progression check is handled inside message model mark()
                            $updated = $msg->mark(
                                $msgStatus,
                                now(),
                                $payload['error'] ?? null
                            );

                            if ($updated) {
                                if ($whatsappMessageId && ! $msg->whatsapp_message_id) {
                                    $msg->update(['whatsapp_message_id' => $whatsappMessageId]);
                                }

                                if ($msgStatus === MessageStatus::Sent) {
                                    event(new WhatsAppMessageSent($msg, $requestId, $whatsappMessageId));
                                } elseif ($msgStatus === MessageStatus::Delivered) {
                                    event(new WhatsAppMessageDelivered($msg, $requestId));
                                } elseif ($msgStatus === MessageStatus::Read) {
                                    event(new WhatsAppMessageRead($msg, $requestId));
                                } elseif ($msgStatus === MessageStatus::Failed) {
                                    event(new WhatsAppMessageFailed($msg, $requestId, $payload['error'] ?? null));
                                }
                            }
                        }
                    }
                    break;
            }

            // Record callback event to guarantee idempotency
            RichWhatsAppCallbackEvent::recordProcessed([
                'event_id' => $eventId,
                'event_type' => $eventType->value,
                'payload' => $payload,
                'message_phone' => $phone,
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Rich WhatsApp callback handling failed', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => ['code' => 'INTERNAL_ERROR', 'message' => $e->getMessage()],
            ], 500);
        }
    }
}
