<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RichnessAgency\RichWhatsApp\Contracts\WhatsApp;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppConversation;
use RichnessAgency\RichWhatsApp\Models\RichWhatsAppMessage;
use RichnessAgency\RichWhatsApp\Enums\MediaType;

class DashboardController extends Controller
{
    public function __construct(
        protected WhatsApp $service
    ) {
        $this->middleware(config('rich-whatsapp.dashboard_middleware', ['web', 'auth']));
    }

    public function index(Request $request)
    {
        if (! $this->service->enabled()) {
            abort(404, 'Rich WhatsApp is disabled.');
        }

        $session = $this->service->sessionStatus();

        // Get conversations paginated
        $conversations = RichWhatsAppConversation::query()
            ->orderByDesc('last_message_at')
            ->paginate(30);

        $activeConversation = null;
        $messages = collect();

        if ($request->has('chat')) {
            $activeConversation = RichWhatsAppConversation::query()
                ->where('whatsapp_chat_id', $request->query('chat'))
                ->orWhere('id', $request->query('chat'))
                ->first();

            if ($activeConversation) {
                // Mark read
                $activeConversation->markRead();

                // Get messages paginated (latest first, but we display them in chronological order)
                $messages = $activeConversation->messages()
                    ->orderByDesc('occurred_at')
                    ->paginate(50);
                
                // Reverse to show chronologically in view
                $messages = collect($messages->items())->reverse();
            }
        }

        return view('rich-whatsapp::dashboard', [
            'session' => $session,
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'messages' => $messages,
        ]);
    }

    public function connect(Request $request)
    {
        if (! $this->service->enabled()) {
            abort(404, 'Rich WhatsApp is disabled.');
        }

        $session = $this->service->sessionStatus();

        if ($session->status->isConnected()) {
            return redirect()->route('rich-whatsapp.dashboard');
        }

        // Trigger session start on Node bridge
        $this->service->startSession();

        // Fetch latest QR
        $qrData = $this->service->qr();

        return view('rich-whatsapp::connect', [
            'session' => $session,
            'qrData' => $qrData,
            'pollSeconds' => config('rich-whatsapp.qr_poll_seconds', 3),
        ]);
    }

    public function reconnect(Request $request)
    {
        $this->service->reconnect();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('status', 'Reconnection request sent.');
    }

    public function logout(Request $request)
    {
        if ($request->isMethod('post')) {
            $this->service->logout();

            if ($request->expectsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('rich-whatsapp.connect')->with('status', 'Logged out successfully.');
        }

        return view('rich-whatsapp::logout');
    }

    public function settings(Request $request)
    {
        return view('rich-whatsapp::settings', [
            'enabled' => $this->service->enabled(),
            'configured' => $this->service->bridgeConfigured(),
            'bridgeUrl' => config('rich-whatsapp.bridge_url'),
            'defaultCountryCode' => config('rich-whatsapp.default_country_code'),
            'storeMessages' => config('rich-whatsapp.store_messages'),
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
            'media' => 'nullable|file|max:' . (config('rich-whatsapp.media_max_mb', 10) * 1024),
        ]);

        $phone = $request->input('phone');
        $text = $request->input('message') ?? '';

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $path = $file->getRealPath();
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            $filename = $file->getClientOriginalName();
            $type = MediaType::fromMime($mime);

            $result = $this->service->sendMedia(
                phone: $phone,
                path: $path,
                type: $type,
                filename: $filename,
                mime: $mime,
                caption: $text
            );
        } else {
            $result = $this->service->sendText($phone, $text);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $result->successful(),
                'request_id' => $result->requestId,
                'message_id' => $result->messageId,
                'status' => $result->status->value,
                'error' => $result->error,
            ]);
        }

        $chatId = $phone . '@s.whatsapp.net';

        return redirect()->route('rich-whatsapp.dashboard', ['chat' => $chatId]);
    }

    public function checkContact(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $registered = $this->service->checkContact($request->input('phone'));

        return response()->json([
            'success' => true,
            'registered' => $registered,
        ]);
    }

    public function status(Request $request)
    {
        $session = $this->service->sessionStatus();

        return response()->json([
            'success' => true,
            'status' => $session->status->value,
            'label' => $session->status->label(),
            'phone' => $session->phone,
            'has_credentials' => $session->hasCredentials,
            'bridge_online' => $session->bridgeOnline,
        ]);
    }

    public function qrCode(Request $request)
    {
        $qrData = $this->service->qr();

        return response()->json([
            'success' => true,
            'qr' => $qrData ? $qrData->qr : null,
            'expires_at' => $qrData ? $qrData->expiresAt : null,
        ]);
    }
}
