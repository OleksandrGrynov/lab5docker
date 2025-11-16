<h2>{{ $chat->name ?? 'Private Chat' }}</h2>

<div id="messages">
    @foreach($chat->messages as $m)
        <p><strong>{{ $m->user->name }}:</strong> {{ $m->body }}</p>
    @endforeach
</div>

<input id="body" placeholder="Message...">
<button onclick="send()">Send</button>

<script src="https://cdn.jsdelivr.net/npm/laravel-reverb@latest"></script>

<script>
    const reverb = new Reverb({
        key: "local",
        wsHost: window.location.hostname,
        wsPort: 8081,
        forceTLS: false,
        path: "/reverb",
    });

    const chatId = {{ $chat->id }};
    const msgBox = document.getElementById("messages");

    reverb.channel(`chat.${chatId}`)
        .listen(".NewMessage", (e) => {
            msgBox.innerHTML += `<p><strong>${e.message.user.name}:</strong> ${e.message.body}</p>`;
        });

    function send() {
        fetch(`/chat/${chatId}/send`, {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ body: document.getElementById("body").value })
        });
    }
</script>
