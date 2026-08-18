@php
    /** @var \RichnessAgency\RichWhatsApp\DTOs\PagedList $chats */
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chats - Rich WhatsApp</title>
    <link rel="stylesheet" href="{{ asset('vendor/rich-whatsapp/css/app.css') }}">
</head>
<body class="rwa-wrapper">
    @include('rich-whatsapp::partials.header', ['rwaActiveNav' => 'chats'])

    @if(! $session->status->isConnected())
        <div style="background-color: var(--rwa-danger); color: #fff; padding: 10px 24px; font-weight: 500; font-size: 14px;">
            <span>WhatsApp is disconnected — history is only available from the bridge while connected.</span>
        </div>
    @endif

    <main class="rwa-main-container">
        <aside class="rwa-sidebar">
            <!-- User own profile header -->
            <div class="rwa-sidebar-profile" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background-color: rgba(255,255,255,0.03); border-bottom: 1px solid var(--rwa-border-color); flex-shrink: 0;">
                <div class="rwa-conv-avatar" style="width: 38px; height: 38px; position: relative; overflow: hidden; background: var(--rwa-primary); border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                    @if($session->phone)
                        <img src="{{ route('rich-whatsapp.picture', ['jid' => $session->phone . '@s.whatsapp.net']) }}" 
                             alt="" 
                             style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; border-radius: 50%;"
                             onerror="this.style.display='none';">
                        <span style="font-size: 13px; font-weight: 700; color: #fff;">Me</span>
                    @else
                        <span style="font-size: 13px; font-weight: 700; color: #fff;">?</span>
                    @endif
                </div>
                <div style="min-width: 0; flex: 1; text-align: left;">
                    <div style="font-size: 13px; font-weight: 600; color: var(--rwa-color-text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">My Account</div>
                    <div style="font-size: 11px; color: var(--rwa-color-text-secondary);">
                        @if($session->phone)
                            +{{ $session->phone }}
                        @else
                            Not connected
                        @endif
                    </div>
                </div>
            </div>

            <div class="rwa-search-box">
                <form action="{{ route('rich-whatsapp.chats') }}" method="GET" style="display: flex; gap: 8px;">
                    <input type="text" name="q" value="{{ $currentQuery }}" class="rwa-search-input" placeholder="Search chats...">
                    <button type="submit" class="rwa-button rwa-button-secondary" style="padding: 6px 12px;">Search</button>
                </form>
            </div>
            <div class="rwa-conv-list">
                @forelse($chats->items as $chat)
                    <a href="{{ route('rich-whatsapp.chat', ['jid' => $chat->jid]) }}"
                       class="rwa-conv-item"
                       data-name="{{ strtolower($chat->name) }}"
                       data-phone="{{ $chat->phone() ?? $chat->jid }}">
                        <div class="rwa-conv-avatar" style="position: relative; overflow: hidden; background: #2a3942; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ route('rich-whatsapp.picture', ['jid' => $chat->jid]) }}" 
                                 alt="" 
                                 style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; border-radius: 50%;"
                                 onerror="this.style.display='none';">
                            <span style="font-size: 14px; font-weight: 600; color: var(--rwa-color-text-secondary);">{{ mb_substr($chat->name, 0, 2) }}</span>
                        </div>
                        <div class="rwa-conv-details">
                            <div class="rwa-conv-header">
                                <span class="rwa-conv-name">{{ $chat->isGroup ? $chat->name : ($chat->name === $chat->phone() || is_numeric($chat->name) ? '+' . $chat->phone() : $chat->name) }}</span>
                                @if($chat->lastMessageAt)
                                    <span class="rwa-conv-time">{{ (new DateTimeImmutable($chat->lastMessageAt))->format('H:i') }}</span>
                                @endif
                            </div>
                            <div class="rwa-conv-header" style="margin-top: 2px;">
                                <span class="rwa-conv-preview">
                                    {{ $chat->lastMessage ? $chat->lastMessage['from_me'] ? 'You: ' . ($chat->lastMessage['text'] ?? '📎') : ($chat->lastMessage['text'] ?? '📎') : 'No messages yet' }}
                                </span>
                                @if($chat->unreadCount > 0)
                                    <span class="rwa-conv-badge">{{ $chat->unreadCount }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div style="padding: 24px; text-align: center; color: var(--rwa-color-text-secondary); font-size: 14px;">
                        @if($currentQuery !== '')
                            No chats match "{{ $currentQuery }}".
                        @else
                            No chats yet. Connect the WhatsApp session to sync chats, contacts and message history.
                        @endif
                    </div>
                @endforelse
            </div>
        </aside>

        <section class="rwa-chat-pane">
            <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--rwa-color-text-secondary); background:
                @if($session->status->isConnected()) linear-gradient(180deg, #004d40 0%, #009688 100%) @else var(--rwa-bg-panel) @endif;">
                <div style="font-size: 64px; margin-bottom: 16px; opacity: 0.5;">💬</div>
                <h3 style="color: #e0f2f1; font-weight: 400;">WhatsApp Web-style chat history</h3>
                <p style="font-size: 14px; color: #b2dfdb;">Pick a conversation to view its full message history.</p>
            </div>
        </section>
    </main>
</body>
</html>