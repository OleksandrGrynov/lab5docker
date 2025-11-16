<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chats') }}
        </h2>
    </x-slot>

    {{-- Стилі для онлайн-крапок та анімації повідомлень --}}
    <style>
        .online-dot {
            width: 10px;
            height: 10px;
            background: #4ade80; /* зелений */
            border-radius: 9999px;
            display: inline-block;
        }
        .offline-dot {
            width: 10px;
            height: 10px;
            background: #9ca3af; /* сірий */
            border-radius: 9999px;
            display: inline-block;
        }

        @keyframes popIn {
            0% { transform: scale(0.95); opacity: 0.2; }
            100% { transform: scale(1); opacity: 1; }
        }
        .msg-new {
            animation: popIn 0.2s ease-out;
        }
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex h-[70vh]">

                    {{-- LEFT: chat list --}}
                    <div class="w-1/3 border-r overflow-y-auto">
                        <a href="{{ route('chats.create') }}"
                           class="block m-3 px-4 py-2 bg-indigo-600 text-white text-center rounded hover:bg-indigo-700">
                            + New Chat
                        </a>

                        @forelse($chats as $chat)
                            @php
                                $isActive = $currentChat && $chat->id === $currentChat->id;
                                $chatName = $chat->name
                                    ?? $chat->users->where('id','!=',auth()->id())->pluck('name')->implode(', ');

                                // перший "інший" користувач у чаті (для приватного чату)
                                $otherUser = $chat->users->where('id','!=',auth()->id())->first();
                            @endphp

                            <a href="{{ route('chats.show', $chat) }}"
                               class="block px-4 py-3 border-b hover:bg-gray-100 {{ $isActive ? 'bg-indigo-50' : '' }}">
                                <div class="font-semibold flex items-center gap-2">
                                    <span
                                        id="status-{{ $chat->id }}"
                                        @if($otherUser)
                                            data-user-id="{{ $otherUser->id }}"
                                        @endif
                                        class="offline-dot"
                                    ></span>
                                    {{ $chatName ?: 'Chat #'.$chat->id }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $chat->type === 'group' ? 'Group chat' : 'Private chat' }}
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-gray-500">No chats yet.</div>
                        @endforelse
                    </div>

                    {{-- RIGHT: active chat --}}
                    <div class="w-2/3 flex flex-col">

                        @if($currentChat)
                            <div class="px-4 py-3 border-b">
                                <h3 class="font-semibold text-lg">
                                    {{ $currentChat->name
                                        ?? $currentChat->users->where('id','!=',auth()->id())->pluck('name')->implode(', ')
                                        ?? 'Chat #'.$currentChat->id }}
                                </h3>
                                <div id="typing-indicator" class="text-xs text-gray-500 mt-1 hidden">
                                    Someone is typing...
                                </div>
                            </div>

                            <div id="messages" class="flex-1 p-4 overflow-y-auto bg-gray-50 space-y-2">
                                @foreach($currentChat->messages as $message)
                                    <div class="flex {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                        <div class="max-w-xs px-3 py-2 rounded-lg text-sm
                                            {{ $message->user_id === auth()->id() ? 'bg-indigo-500 text-white' : 'bg-white border' }}">
                                            <div class="text-xs font-semibold mb-1">
                                                {{ $message->user->name }}
                                            </div>
                                            <div>{{ $message->body }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t p-3 flex gap-2">
                                <input id="message-input" type="text"
                                       class="flex-1 border rounded px-3 py-2 text-sm"
                                       placeholder="Type message...">

                                <button id="send-btn"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                    Send
                                </button>
                            </div>
                        @else
                            <div class="flex-1 flex items-center justify-center text-gray-500">
                                Select chat from the list
                            </div>
                        @endif

                    </div>

                </div>
            </div>
        </div>
    </div>

    @if($currentChat)
        {{-- звук для нового повідомлення --}}
        <audio id="msg-sound" src="/sounds/message.mp3" preload="auto"></audio>

        <script>
            const CURRENT_CHAT_ID = {{ $currentChat->id }};
            const CURRENT_USER_ID = {{ auth()->id() }};

            const ws = new WebSocket("ws://" + window.location.hostname + ":8081");

            const sound = document.getElementById("msg-sound");
            const typingEl = document.getElementById("typing-indicator");

            ws.onopen = () => {
                console.log("WS connected!");

                // авторизація на WS (щоб сервер знав, який юзер)
                ws.send(JSON.stringify({
                    type: "auth",
                    user_id: CURRENT_USER_ID
                }));
            };

            ws.onmessage = event => {
                const msg = JSON.parse(event.data);

                // --- ONLINE / OFFLINE статус (якщо оновиш server.js під user_status) ---
                if (msg.type === "user_status") {
                    updateUserStatus(msg.user_id, msg.status);
                }

                // старі події, якщо ще залишилися
                if (msg.type === "user_connected") {
                    console.log("👤 User online");
                }
                if (msg.type === "user_disconnected") {
                    console.log("👤 User offline");
                }

                // --- повідомлення ---
                if (msg.chat_id && msg.chat_id === CURRENT_CHAT_ID) {
                    appendMessage(msg);

                    // якщо це не моє повідомлення — звук
                    if (msg.user_id !== CURRENT_USER_ID && sound) {
                        sound.pause();
                        sound.currentTime = 0;
                        sound.play().catch(() => {});
                    }
                }

                // --- typing indicator ---
                if (msg.type === "typing" && msg.chat_id === CURRENT_CHAT_ID) {
                    showTyping();
                }
            };

            function updateUserStatus(userId, status) {
                document.querySelectorAll('[data-user-id="' + userId + '"]').forEach(el => {
                    el.classList.remove('online-dot', 'offline-dot');
                    el.classList.add(status === 'online' ? 'online-dot' : 'offline-dot');
                });
            }

            function appendMessage(m) {
                const mine = m.user_id === CURRENT_USER_ID;
                const messagesEl = document.getElementById('messages');

                const wrapper = document.createElement('div');
                wrapper.className = "flex " + (mine ? "justify-end" : "justify-start");

                const highlight = mine ? '' : 'bg-yellow-50';

                wrapper.innerHTML = `
                    <div class="max-w-xs px-3 py-2 rounded-lg text-sm ${mine ? 'bg-indigo-500 text-white' : 'bg-white border'} ${highlight} msg-new">
                        <div class="text-xs font-semibold mb-1">${m.user.name}</div>
                        <div>${m.body}</div>
                    </div>
                `;

                messagesEl.appendChild(wrapper);
                messagesEl.scrollTop = messagesEl.scrollHeight;

                // прибрати жовту підсвітку через мить
                const bubble = wrapper.querySelector('div');
                setTimeout(() => {
                    bubble.classList.remove('bg-yellow-50');
                }, 600);
            }

            // --- send message ---
            document.getElementById("send-btn").onclick = sendMessage;

            function sendMessage() {
                const input = document.getElementById("message-input");
                const body = input.value.trim();
                if (!body) return;

                fetch("{{ route('chats.send', $currentChat) }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ body })
                })
                    .then(r => r.json())
                    .then(res => {
                        // надсилаємо у WS (твоя стара логіка)
                        ws.send(JSON.stringify(res.message));
                    })
                    .catch(console.error);

                input.value = "";
            }

            // --- TYPING ---
            let typingTimer;
            const input = document.getElementById("message-input");

            input.addEventListener("input", () => {
                clearTimeout(typingTimer);

                ws.send(JSON.stringify({
                    type: "typing",
                    chat_id: CURRENT_CHAT_ID,
                    user_id: CURRENT_USER_ID
                }));

                typingTimer = setTimeout(() => {}, 1000);
            });

            function showTyping() {
                if (!typingEl) return;

                typingEl.classList.remove("hidden");

                clearTimeout(window._typingHideTimer);
                window._typingHideTimer = setTimeout(() => {
                    typingEl.classList.add("hidden");
                }, 1200);
            }
        </script>
    @endif

</x-app-layout>
