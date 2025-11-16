<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Messenger Test</title>

    <script src="https://cdn.jsdelivr.net/npm/laravel-reverb@latest"></script>

    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        #messages p { background: #f2f2f2; padding: 8px; border-radius: 5px; }
    </style>
</head>
<body>

<h1>WebSocket Chat</h1>

<div id="messages"></div>

<input id="input" placeholder="Type a message...">
<button onclick="send()">Send</button>

<script>
    const reverb = new Reverb({
        key: "local",
        wsHost: window.location.hostname,
        wsPort: 8081,
        forceTLS: false,
        path: "/reverb",
    });

    reverb.channel("chat")
        .listen(".MessageSent", (event) => {
            const messages = document.getElementById("messages");
            messages.innerHTML += `<p>${event.message}</p>`;
        });

    function send() {
        fetch("/send", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                message: document.getElementById("input").value
            })
        });
    }
</script>

</body>
</html>
