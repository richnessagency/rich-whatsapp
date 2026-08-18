@php
    /** @var \RichnessAgency\RichWhatsApp\DTOs\ChatHistory|null $history */
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

    <main class="rwa-main-container rwa-thread-container">
        <!-- Thread Header -->
        <section class="rwa-chat-header" style="flex: none; display: flex; justify-content: space-between; align-items: center; padding: 12px 20px;">
            <div class="rwa-chat-title-info" style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                <a href="{{ route('rich-whatsapp.chats') }}" style="text-decoration: none; font-size: 20px; color: var(--rwa-color-text-secondary);" title="Back to chats">←</a>
                @if($session->status->isConnected())
                    <img src="{{ route('rich-whatsapp.picture', ['jid' => $jid]) }}"
                         alt=""
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: var(--rwa-bg-panel);"
                         onerror="this.style.display='none'">
                @endif
                <div class="rwa-conv-avatar" style="width: 40px; height: 40px;">{{ mb_substr($name, 0, 2) }}</div>
                <div style="min-width: 0;">
                    <h3 style="margin: 0; font-size: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $name }}</h3>
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
        </section>

        <!-- Messages -->
        <div class="rwa-chat-messages-container rwa-thread-messages" id="chat-messages" data-jid="{{ $jid }}" data-name="{{ $name }}" data-phone="{{ $phone }}">
            @if($history && $history->hasMore)
                <div style="text-align: center; margin: 12px 0;">
                    <a href="{{ route('rich-whatsapp.chat', ['jid' => $jid, 'before' => $history->nextCursor]) }}"
                       class="rwa-button rwa-button-secondary" style="padding: 6px 14px; font-size: 12px;">↩ Load older messages</a>
                </div>
            @endif

            @forelse(($history?->messages ?? []) as $msg)
                <div class="rwa-message {{ $msg->fromMe ? 'rwa-message-out' : 'rwa-message-in' }}" data-mid="{{ $msg->id }}">
                    @if($msg->isMedia)
                        <div style="margin-bottom: 6px; border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.06); padding: 4px; max-width: 320px;">
                            @if($msg->type === 'image')
                                <img src="{{ route('rich-whatsapp.media', ['jid' => $jid, 'messageId' => $msg->id]) }}"
                                     alt="{{ $msg->caption ?: 'Image' }}"
                                     loading="lazy"
                                     style="max-width: 320px; max-height: 260px; display: block; border-radius: 6px;"
                                     onerror="this.outerHTML = '<div style=\"color:var(--rwa-color-text-secondary);font-size:12px;padding:8px;\">Image unavailable</div>';">
                            @elseif($msg->type === 'sticker')
                                <img src="{{ route('rich-whatsapp.media', ['jid' => $jid, 'messageId' => $msg->id]) }}"
                                     alt="Sticker"
                                     loading="lazy"
                                     style="max-width: 160px; display: block; border-radius: 6px;"
                                     onerror="this.outerHTML = '<div style=\"color:var(--rwa-color-text-secondary);font-size:12px;padding:8px;\">Sticker unavailable</div>';">
                            @else
                                <div style="display: flex; align-items: center; gap: 8px; padding: 10px;">
                                    <span style="font-size: 24px;">@if($msg->type === 'audio') 🎵 @elseif($msg->type === 'video') 🎬 @else 📄 @endif</span>
                                    <a href="{{ route('rich-whatsapp.media', ['jid' => $jid, 'messageId' => $msg->id]) }}" style="min-width: 0;">
                                        <div style="font-weight: 500; font-size: 13px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">{{ $msg->filename ?: ucfirst($msg->type) }}</div>
                                        <div style="font-size: 11px; color: var(--rwa-color-text-secondary);">{{ $msg->mimetype ?: strtoupper($msg->type) }}</div>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($msg->displayText() !== '' && ! $msg->isMedia)
                        <div style="font-size: 14.5px; line-height: 1.4; word-break: break-word;">{{ $msg->displayText() }}</div>
                    @elseif($msg->caption)
                        <div style="font-size: 14.5px; line-height: 1.4; word-break: break-word; margin-top: 4px;">{{ $msg->caption }}</div>
                    @endif

                    <div class="rwa-msg-meta">
                        @if($msg->time())<span>{{ $msg->time() }}</span>@endif
                        @if($msg->fromMe)<span class="rwa-msg-status-tick">✓✓</span>@endif
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--rwa-color-text-secondary); padding-top: 40px;">
                    No messages in this chat yet.
                </div>
            @endforelse
        </div>

        <!-- Composer -->
        <div class="rwa-composer" style="flex: none;">
            <form action="{{ route('rich-whatsapp.messages.send') }}" method="POST" enctype="multipart/form-data" class="rwa-composer-form">
                @csrf
                <input type="hidden" name="phone" value="{{ $phone }}">
                <label for="media-file" style="cursor: pointer; padding: 8px; font-size: 20px; background: transparent; border: none;" title="Attach File">
                    📎
                    <input type="file" id="media-file" name="media" style="display: none;" onchange="fileSelected(this)">
                </label>
                <span id="file-indicator" style="display: none; font-size: 12px; color: var(--rwa-primary); max-width: 100px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; margin-right: 8px;"></span>
                <input type="text" name="message" class="rwa-composer-input" placeholder="Type a message..." autocomplete="off" required>
                <button type="submit" class="rwa-button">Send</button>
            </form>
        </div>
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