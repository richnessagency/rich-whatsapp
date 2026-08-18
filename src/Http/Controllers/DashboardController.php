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

        if ($session->status->isConnected()) {
            return redirect()->route('rich-whatsapp.chats');
        }

        return redirect()->route('rich-whatsapp.connect');
    }

    /** WhatsApp Web-style full-height chats list (live from the bridge). */
    public function chats(Request $request)
    {
        if (! $this->service->enabled()) {
            abort(404, 'Rich WhatsApp is disabled.');
        }

        $query = trim((string) $request->query('q', ''));

        return view('rich-whatsapp::chats', [
            'session' => $this->service->sessionStatus(),
            'chats' => $this->service->listChats($query !== '' ? $query : null, 100, 0),
            'currentQuery' => $query,
        ]);
    }

    public function chat(Request $request, string $jid)
    {
        if (! $this->service->enabled()) {
            abort(404, 'Rich WhatsApp is disabled.');
        }

        $limit = (int) ($request->query('limit', 50));
        $before = $request->query('before') ? (string) $request->query('before') : null;

        $history = $this->service->chatMessages($jid, $limit, $before);
        $chats = $this->service->listChats(null, 100, 0);
        $chat = collect($chats->items)->first(
            static fn ($c) => $c->jid === $jid
        );

        return view('rich-whatsapp::chat', [
            'session' => $this->service->sessionStatus(),
            'history' => $history,
            'chats' => $chats,
            'jid' => $jid,
            'name' => $chat?->name ?? $this->phoneFromJid($jid),
            'phone' => $this->phoneFromJid($jid),
            'isGroup' => str_contains($jid, '@g.us'),
            'limit' => $limit,
        ]);
    }

    /** Contacts browser (live from the bridge). */
    public function contacts(Request $request)
    {
        if (! $this->service->enabled()) {
            abort(404, 'Rich WhatsApp is disabled.');
        }

        $query = trim((string) $request->query('q', ''));

        return view('rich-whatsapp::contacts', [
            'session' => $this->service->sessionStatus(),
            'contacts' => $this->service->listContacts($query !== '' ? $query : null, 200, 0),
            'currentQuery' => $query,
        ]);
    }

    /** Streams a single message's media through the bridge (binary). */
    public function media(string $jid, string $messageId)
    {
        $data = $this->service->chatMedia($jid, $messageId);

        if ($data === null) {
            abort(404, 'Media unavailable.');
        }

        return response($data['body'], 200, [
            'Content-Type' => $data['content_type'] ?: 'application/octet-stream',
        ])->setCache(['private' => true, 'max_age' => 300]);
    }

    /** Streams a chat profile picture through the bridge (binary). */
    public function picture(string $jid)
    {
        $data = $this->service->chatPicture($jid);

        if ($data === null) {
            abort(404, 'No profile picture available.');
        }

        return response($data['body'], 200, [
            'Content-Type' => $data['content_type'] ?: 'image/jpeg',
        ])->setCache(['private' => true, 'max_age' => 300]);
    }

    protected function phoneFromJid(string $jid): string
    {
        return explode('@', $jid, 2)[0];
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

        return redirect()->route('rich-whatsapp.chat', ['jid' => $chatId]);
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
