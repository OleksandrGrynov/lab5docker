<?php

namespace App\Http\Controllers;

use App\Events\NewMessage;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // завантажуємо чати + список юзерів
        $chats = $user->chats()->with('users')->get();

        // візьмемо перший чат як вибраний (якщо є)
        $currentChat = $chats->first()?->load('messages.user');

        return view('chats.index', compact('chats', 'currentChat'));
    }
    public function create()
    {
        $users = \App\Models\User::where('id', '!=', auth()->id())->get();
        return view('chats.create', compact('users'));
    }

    public function createPrivate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $current = auth()->id();
        $other = $request->user_id;

        // перевірка чи існує приватний чат
        $chat = Chat::where('type', 'private')
            ->whereHas('users', fn($q) => $q->where('user_id', $current))
            ->whereHas('users', fn($q) => $q->where('user_id', $other))
            ->first();

        if (!$chat) {
            $chat = Chat::create(['type' => 'private']);
            $chat->users()->attach([$current, $other]);
        }

        return redirect()->route('chats.show', $chat);
    }

    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'users' => 'required|array|min:1'
        ]);

        $chat = Chat::create([
            'type' => 'group',
            'name' => $request->name
        ]);

        $chat->users()->attach(array_merge($request->users, [auth()->id()]));

        return redirect()->route('chats.show', $chat);
    }

    public function show(Chat $chat)
    {
        $chat->load('messages.user', 'users');

        $chats = auth()->user()->chats()->with('users')->get();

        return view('chats.index', [
            'chats' => $chats,
            'currentChat' => $chat,
        ]);
    }

    public function send(Request $request, Chat $chat)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => auth()->id(),
            'body'    => $request->body,
        ]);

        return response()->json([
            'ok' => true,
            'message' => $message->load('user')
        ]);

    }
}
