@php
    /** @var \RichnessAgency\RichWhatsApp\DTOs\ChatHistory|null $history */
    /** @var \RichnessAgency\RichWhatsApp\DTOs\PagedList $chats */
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $name }} - Rich WhatsApp</title>
    <link rel="stylesheet" href="{{ asset('vendor/rich-whatsapp/css/app.css') }}">
</head>
<body class="rwa-wrapper">
    @include('rich-whatsapp::partials.header', ['rwaActiveNav' => 'chats'])

    <main class="rwa-main-container">
        <!-- Sidebar (Left) -->
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
                    <input type="text" name="q" value="" class="rwa-search-input" placeholder="Search chats...">
                    <button type="submit" class="rwa-button rwa-button-secondary" style="padding: 6px 12px;">Search</button>
                </form>
            </div>
            <div class="rwa-conv-list">
                @forelse($chats->items as $c)
                    <a href="{{ route('rich-whatsapp.chat', ['jid' => $c->jid]) }}"
                       class="rwa-conv-item {{ $jid === $c->jid ? 'rwa-active' : '' }}"
                       data-name="{{ strtolower($c->name) }}"
                       data-phone="{{ $c->phone() ?? $c->jid }}">
                        <div class="rwa-conv-avatar" style="position: relative; overflow: hidden; background: #2a3942; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ route('rich-whatsapp.picture', ['jid' => $c->jid]) }}" 
                                 alt="" 
                                 style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; border-radius: 50%;"
                                 onerror="this.style.display='none';">
                            <span style="font-size: 14px; font-weight: 600; color: var(--rwa-color-text-secondary);">{{ mb_substr($c->name, 0, 2) }}</span>
                        </div>
                        <div class="rwa-conv-details">
                            <div class="rwa-conv-header">
                                <span class="rwa-conv-name">{{ $c->isGroup ? $c->name : ($c->name === $c->phone() || is_numeric($c->name) ? '+' . $c->phone() : $c->name) }}</span>
                                @if($c->lastMessageAt)
                                    <span class="rwa-conv-time">{{ (new DateTimeImmutable($c->lastMessageAt))->format('H:i') }}</span>
                                @endif
                            </div>
                            <div class="rwa-conv-header" style="margin-top: 2px;">
                                <span class="rwa-conv-preview">
                                    {{ $c->lastMessage ? $c->lastMessage['from_me'] ? 'You: ' . ($c->lastMessage['text'] ?? '📎') : ($c->lastMessage['text'] ?? '📎') : 'No messages yet' }}
                                </span>
                                @if($c->unreadCount > 0)
                                    <span class="rwa-conv-badge">{{ $c->unreadCount }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div style="padding: 24px; text-align: center; color: var(--rwa-color-text-secondary); font-size: 14px;">
                        No chats yet.
                    </div>
                @endforelse
            </div>
        </aside>

        <!-- Chat Pane (Right) -->
        <section class="rwa-chat-pane">
            <!-- Thread Header -->
            <div class="rwa-chat-header" style="flex: none; display: flex; justify-content: space-between; align-items: center; padding: 10px 20px;">
                <div class="rwa-chat-title-info" style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                    <div class="rwa-conv-avatar" style="width: 40px; height: 40px; position: relative; overflow: hidden; background: #2a3942; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ route('rich-whatsapp.picture', ['jid' => $jid]) }}"
                             alt=""
                             style="width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; border-radius: 50%;"
                             onerror="this.style.display='none';">
                        <span style="font-size: 14px; font-weight: 600; color: var(--rwa-color-text-secondary);">{{ mb_substr($name, 0, 2) }}</span>
                    </div>
                    <div style="min-width: 0;">
                        <h3 style="margin: 0; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $isGroup ? $name : (is_numeric($name) ? '+' . $name : $name) }}</h3>
                        <span style="font-size: 12px; color: var(--rwa-color-text-secondary);">@if($isGroup) Group @else +{{ $phone }} @endif · {{ $history?->total ?? 0 }} messages</span>
                    </div>
                </div>
                <div class="rwa-status-badge">
                    @if($session->status->isConnected())
                        <span class="rwa-status-dot rwa-status-connected"></span>
                    @else
                        <span class="rwa-status-dot rwa-status-offline"></span>
                    @endif
                    <span>{{ $session->status->label() }}</span>
                </div>
            </div>

            <!-- Messages Container -->
            <div class="rwa-chat-messages-container" id="chat-messages" data-jid="{{ $jid }}" data-name="{{ $name }}" data-phone="{{ $phone }}">
                @if($history && $history->hasMore)
                    <div style="text-align: center; margin: 12px 0;">
                        <a href="{{ route('rich-whatsapp.chat', ['jid' => $jid, 'before' => $history->nextCursor]) }}"
                           class="rwa-button rwa-button-secondary" style="padding: 6px 14px; font-size: 12px;">↩ Load older messages</a>
                    </div>
                @endif

                @forelse(($history?->messages ?? []) as $msg)
                    @if($msg->isSystem)
                        <div class="rwa-message-system" style="text-align: center; margin: 8px 20px;">
                            <span style="display: inline-block; padding: 4px 14px; border-radius: 8px; background: rgba(255,255,255,0.06); color: var(--rwa-color-text-secondary); font-size: 12px; line-height: 1.4;">
                                {{ $msg->displayText() }}
                            </span>
                        </div>
                    @else
                    <div class="rwa-message {{ $msg->fromMe ? 'rwa-message-out' : 'rwa-message-in' }}" data-mid="{{ $msg->id }}">
                        @if($msg->isMedia)
                            <div class="rwa-media-box" style="margin-bottom: 6px; border-radius: 6px; overflow: hidden; background: rgba(0,0,0,0.15); padding: 4px; display: inline-block;">
                                @if($msg->type === 'image')
                                    <img src="{{ route('rich-whatsapp.media', ['jid' => $jid, 'messageId' => $msg->id]) }}"
                                         alt="{{ $msg->caption ?: 'Image' }}"
                                         loading="lazy"
                                         style="max-width: 100%; max-height: 280px; display: block; border-radius: 4px; cursor: pointer;"
                                         onclick="window.open(this.src)">
                                @elseif($msg->type === 'sticker')
                                    <img src="{{ route('rich-whatsapp.media', ['jid' => $jid, 'messageId' => $msg->id]) }}"
                                         alt="Sticker"
                                         loading="lazy"
                                         style="max-width: 140px; display: block;"
                                         onerror="this.outerHTML = '<div style=\"color:var(--rwa-color-text-secondary);font-size:12px;padding:8px;\">Sticker unavailable</div>';">
                                @elseif($msg->type === 'audio')
                                    <div style="padding: 6px 8px; min-width: 240px; display: flex; flex-direction: column; gap: 4px;">
                                        <div style="display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--rwa-color-text-secondary);">
                                            <span>🎤 Voice Note</span>
                                        </div>
                                        <audio controls style="width: 100%; height: 32px;" preload="metadata"
                                               src="{{ route('rich-whatsapp.media', ['jid' => $jid, 'messageId' => $msg->id]) }}">
                                            Your browser does not support the audio tag.
                                        </audio>
                                    </div>
                                @else
                                    <div style="display: flex; align-items: center; gap: 10px; padding: 6px 10px;">
                                        <span style="font-size: 20px;">@if($msg->type === 'audio') 🎵 @elseif($msg->type === 'video') 🎬 @else 📄 @endif</span>
                                        <div style="min-width: 0; text-align: left;">
                                            <a href="{{ route('rich-whatsapp.media', ['jid' => $jid, 'messageId' => $msg->id]) }}" target="_blank" style="font-weight: 500; font-size: 13px; text-decoration: none; color: var(--rwa-primary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap; display: block;">
                                                {{ $msg->filename ?: ucfirst($msg->type) }}
                                            </a>
                                            <div style="font-size: 11px; color: var(--rwa-color-text-secondary);">{{ $msg->mimetype ?: strtoupper($msg->type) }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($msg->displayText() !== '' && ! $msg->isMedia)
                            <div style="font-size: 14.5px; line-height: 1.4; word-break: break-word; text-align: left;">{{ $msg->displayText() }}</div>
                        @elseif($msg->caption)
                            <div style="font-size: 14.5px; line-height: 1.4; word-break: break-word; margin-top: 4px; text-align: left;">{{ $msg->caption }}</div>
                        @endif

                        <div class="rwa-msg-meta">
                            @if($msg->time())<span>{{ $msg->time() }}</span>@endif
                            @if($msg->fromMe)<span class="rwa-msg-status-tick">✓✓</span>@endif
                        </div>
                    </div>
                    @endif
                @empty
                    <div style="text-align: center; color: var(--rwa-color-text-secondary); padding-top: 40px;">
                        No messages in this chat yet.
                    </div>
                @endforelse
            </div>

            <!-- Composer Form -->
            <div class="rwa-composer">
                <form action="{{ route('rich-whatsapp.messages.send') }}" method="POST" enctype="multipart/form-data" class="rwa-composer-form">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $phone }}">
                    <label for="media-file" style="cursor: pointer; padding: 8px; font-size: 20px; background: transparent; border: none; display: flex; align-items: center;" title="Attach File">
                        📎
                        <input type="file" id="media-file" name="media" style="display: none;" onchange="fileSelected(this)">
                    </label>
                    <span id="file-indicator" style="display: none; font-size: 12px; color: var(--rwa-primary); max-width: 120px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; margin-right: 8px;"></span>
                    <input type="text" name="message" class="rwa-composer-input" placeholder="Type a message..." autocomplete="off" required>
                    <button type="submit" class="rwa-button">Send</button>
                </form>
            </div>
        </section>
    </main>

    <script>
        const chatContainer = document.getElementById('chat-messages');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        function fileSelected(input) {
            const indicator = document.getElementById('file-indicator');
            if (input.files && input.files[0]) {
                indicator.innerText = input.files[0].name;
                indicator.style.display = 'inline-block';
            } else {
                indicator.style.display = 'none';
            }
        }
    </script>
</body>
</html>