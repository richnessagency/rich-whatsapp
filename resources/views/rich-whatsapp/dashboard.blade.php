<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Dashboard - Rich WhatsApp</title>
    <link rel="stylesheet" href="{{ asset('vendor/rich-whatsapp/css/app.css') }}">
    <style>
        /* Fallback embedded support */
        {!! file_get_contents(__DIR__ . '/../../css/app.css') !!}
    </style>
</head>
<body class="rwa-wrapper">
    <header class="rwa-header">
        <div class="rwa-logo-container">
            <div class="rwa-logo-icon">W</div>
            <div class="rwa-logo-text">Rich WhatsApp</div>
        </div>
        <div style="display: flex; gap: 16px; align-items: center;">
            <a href="{{ route('rich-whatsapp.settings') }}" class="rwa-button rwa-button-secondary" style="padding: 6px 14px;">Diagnostics</a>
            
            <button onclick="document.getElementById('new-msg-dialog').showModal();" class="rwa-button" style="padding: 6px 14px;">+ New Chat</button>

            @if($session->status->isConnected())
                <button onclick="document.getElementById('logout-dialog').showModal();" class="rwa-button rwa-button-danger" style="padding: 6px 14px;">Logout</button>
            @else
                <a href="{{ route('rich-whatsapp.connect') }}" class="rwa-button" style="padding: 6px 14px;">Connect</a>
            @endif

            <div class="rwa-status-badge">
                <span class="rwa-status-dot rwa-status-{{ $session->status->isConnected() ? 'connected' : ($session->status->value === 'qr_required' ? 'qr_required' : ($session->status->value === 'connecting' || $session->status->value === 'reconnecting' ? 'connecting' : 'offline')) }}"></span>
                <span>{{ $session->status->label() }}</span>
            </div>
        </div>
    </header>

    @if(! $session->status->isConnected())
        <div style="background-color: var(--rwa-danger); color: #fff; padding: 10px 24px; font-weight: 500; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
            <span>WhatsApp is currently disconnected. Outgoing messages will be queued.</span>
            <div style="display: flex; gap: 12px;">
                <form action="{{ route('rich-whatsapp.reconnect') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="rwa-button rwa-button-secondary" style="padding: 4px 10px; font-size: 12px; background: rgba(255,255,255,0.2); border: none;">Reconnect</button>
                </form>
                <a href="{{ route('rich-whatsapp.connect') }}" class="rwa-button" style="padding: 4px 10px; font-size: 12px; background: #fff; color: var(--rwa-danger);">Scan QR</a>
            </div>
        </div>
    @endif

    <main class="rwa-main-container">
        <!-- Sidebar -->
        <aside class="rwa-sidebar" id="sidebar">
            <div class="rwa-search-box">
                <input type="text" id="conv-search" class="rwa-search-input" placeholder="Search chats..." oninput="filterConversations()">
            </div>
            <div class="rwa-conv-list">
                @forelse($conversations as $conv)
                    <a href="{{ route('rich-whatsapp.dashboard', ['chat' => $conv->whatsapp_chat_id]) }}" 
                       class="rwa-conv-item {{ $activeConversation && $activeConversation->id === $conv->id ? 'rwa-active' : '' }}"
                       data-name="{{ strtolower($conv->display_name ?: '') }}"
                       data-phone="{{ $conv->phone }}">
                        <div class="rwa-conv-avatar">
                            {{ mb_substr($conv->display_name ?: $conv->phone, 0, 2) }}
                        </div>
                        <div class="rwa-conv-details">
                            <div class="rwa-conv-header">
                                <span class="rwa-conv-name">{{ $conv->display_name ?: '+' . $conv->phone }}</span>
                                <span class="rwa-conv-time">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : '' }}</span>
                            </div>
                            <div class="rwa-conv-header" style="margin-top: 2px;">
                                <span class="rwa-conv-preview">{{ $conv->last_message_preview ?: 'No messages yet' }}</span>
                                @if($conv->unread_count > 0)
                                    <span class="rwa-conv-badge">{{ $conv->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div style="padding: 24px; text-align: center; color: var(--rwa-color-text-secondary); font-size: 14px;">
                        No conversations found.
                    </div>
                @endforelse
            </div>
        </aside>

        <!-- Chat Pane -->
        <section class="rwa-chat-pane">
            @if($activeConversation)
                <div class="rwa-chat-header">
                    <div class="rwa-chat-title-info">
                        <div class="rwa-conv-avatar">
                            {{ mb_substr($activeConversation->display_name ?: $activeConversation->phone, 0, 2) }}
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 16px;">{{ $activeConversation->display_name ?: '+' . $activeConversation->phone }}</h3>
                            <span style="font-size: 12px; color: var(--rwa-color-text-secondary);">+{{ $activeConversation->phone }}</span>
                        </div>
                    </div>
                </div>

                <div class="rwa-chat-messages-container" id="chat-messages">
                    @foreach($messages as $msg)
                        <div class="rwa-message {{ $msg->direction->value === 'incoming' ? 'rwa-message-in' : 'rwa-message-out' }}">
                            @if($msg->media_type)
                                <div style="margin-bottom: 6px; border-radius: 4px; overflow: hidden; background: rgba(0,0,0,0.1); padding: 4px;">
                                    @if($msg->media_type === 'image')
                                        <img src="{{ $msg->media_path_or_reference }}" alt="Image" style="max-width: 100%; max-height: 200px; display: block; border-radius: 4px;">
                                    @else
                                        <div style="display: flex; align-items: center; gap: 8px; padding: 8px;">
                                            <span style="font-size: 24px;">📁</span>
                                            <div style="min-width: 0;">
                                                <div style="font-weight: 500; font-size: 13px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">{{ $msg->media_name }}</div>
                                                <div style="font-size: 11px; color: var(--rwa-color-text-secondary);">{{ strtoupper($msg->media_type) }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($msg->body)
                                <div style="font-size: 14.5px; line-height: 1.4; word-break: break-word;">{{ $msg->body }}</div>
                            @endif

                            <div class="rwa-msg-meta">
                                <span>{{ $msg->occurred_at ? $msg->occurred_at->format('H:i') : '' }}</span>
                                @if($msg->direction->value === 'outgoing')
                                    <span class="rwa-msg-status-tick" title="{{ ucfirst($msg->status->value) }}">
                                        @if($msg->status->value === 'read')
                                            <span style="color: #53bdeb;">✓✓</span>
                                        @elseif($msg->status->value === 'delivered')
                                            <span>✓✓</span>
                                        @elseif($msg->status->value === 'sent' || $msg->status->value === 'submitted')
                                            <span>✓</span>
                                        @elseif($msg->status->value === 'queued')
                                            <span>⏳</span>
                                        @elseif($msg->status->value === 'failed')
                                            <span style="color: var(--rwa-danger);" title="{{ $msg->failure_reason }}">⚠️</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Composer -->
                <div class="rwa-composer">
                    <form action="{{ route('rich-whatsapp.messages.send') }}" method="POST" enctype="multipart/form-data" class="rwa-composer-form">
                        @csrf
                        <input type="hidden" name="phone" value="{{ $activeConversation->phone }}">
                        
                        <label for="media-file" style="cursor: pointer; padding: 8px; font-size: 20px; background: transparent; border: none;" title="Attach File">
                            📎
                            <input type="file" id="media-file" name="media" style="display: none;" onchange="fileSelected(this)">
                        </label>
                        
                        <span id="file-indicator" style="display: none; font-size: 12px; color: var(--rwa-primary); max-width: 100px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; margin-right: 8px;"></span>

                        <input type="text" name="message" class="rwa-composer-input" placeholder="Type a message..." autocomplete="off" required>
                        <button type="submit" class="rwa-button">Send</button>
                    </form>
                </div>
            @else
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--rwa-color-text-secondary);">
                    <div style="font-size: 48px; margin-bottom: 16px;">💬</div>
                    <h3>Select a conversation or start a new chat</h3>
                </div>
            @endif
        </section>
    </main>

    <!-- Dialogs -->
    <dialog id="new-msg-dialog" class="rwa-dialog">
        <h3 style="margin-top: 0;">Start New Conversation</h3>
        <form action="{{ route('rich-whatsapp.messages.send') }}" method="POST">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; color: var(--rwa-color-text-secondary); margin-bottom: 6px;">Phone Number</label>
                <input type="text" name="phone" class="rwa-search-input" placeholder="e.g. +201012345678 or 01012345678" required style="background: var(--rwa-bg-panel); border: 1px solid var(--rwa-border-color);">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: var(--rwa-color-text-secondary); margin-bottom: 6px;">Initial Message</label>
                <input type="text" name="message" class="rwa-search-input" placeholder="Type first message..." required style="background: var(--rwa-bg-panel); border: 1px solid var(--rwa-border-color);">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="document.getElementById('new-msg-dialog').close();" class="rwa-button rwa-button-secondary">Cancel</button>
                <button type="submit" class="rwa-button">Send Message</button>
            </div>
        </form>
    </dialog>

    <dialog id="logout-dialog" class="rwa-dialog">
        <h3 style="margin-top: 0;">Unlink WhatsApp Session?</h3>
        <p style="font-size: 14px; line-height: 1.5; color: var(--rwa-color-text-secondary);">Logging out will unlink this WhatsApp session and a new QR scan will be required. Are you sure you want to continue?</p>
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
            <button type="button" onclick="document.getElementById('logout-dialog').close();" class="rwa-button rwa-button-secondary">Cancel</button>
            <form action="{{ route('rich-whatsapp.logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="rwa-button rwa-button-danger">Logout</button>
            </form>
        </div>
    </dialog>

    <script>
        // Auto scroll messages to bottom
        const chatContainer = document.getElementById('chat-messages');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Conversation search filter
        function filterConversations() {
            const query = document.getElementById('conv-search').value.toLowerCase();
            const items = document.querySelectorAll('.rwa-conv-item');

            items.forEach(item => {
                const name = item.getAttribute('data-name');
                const phone = item.getAttribute('data-phone');
                if (name.includes(query) || phone.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
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
