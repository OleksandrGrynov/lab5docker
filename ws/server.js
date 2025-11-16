const { WebSocketServer } = require("ws");

const wss = new WebSocketServer({ port: 8081 });

console.log("🔥 WebSocket server running on ws://0.0.0.0:8081");

let usersOnline = new Map(); // userId → ws

wss.on("connection", (ws) => {
    console.log("Client connected");

    ws.on("message", (raw) => {
        let data = {};

        try {
            data = JSON.parse(raw);
        } catch {
            return;
        }

        // 1️⃣ Користувач підключився та прислав свій ID
        if (data.type === "auth") {
            ws.user_id = data.user_id;
            usersOnline.set(data.user_id, ws);

            broadcast({
                type: "user_status",
                user_id: ws.user_id,
                status: "online"
            });
            return;
        }

        // 2️⃣ typing
        if (data.type === "typing") {
            broadcast(data);
            return;
        }

        // 3️⃣ нове повідомлення
        if (data.chat_id && data.body) {
            broadcast(data);
            return;
        }
    });

    ws.on("close", () => {
        if (ws.user_id) {
            usersOnline.delete(ws.user_id);

            broadcast({
                type: "user_status",
                user_id: ws.user_id,
                status: "offline"
            });
        }
    });
});

// Глобальна розсилка
function broadcast(msg) {
    const json = JSON.stringify(msg);

    wss.clients.forEach(c => {
        if (c.readyState === 1) c.send(json);
    });
}
