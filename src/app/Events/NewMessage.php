<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use SerializesModels;

    public $message;
    public $chatId;

    public function __construct(Message $message)
    {
        $this->message = $message->load('user');
        $this->chatId = $message->chat_id;
    }

    public function broadcastOn()
    {
        return new Channel("chat.{$this->chatId}");
    }

    public function broadcastAs()
    {
        return 'NewMessage';
    }
}
