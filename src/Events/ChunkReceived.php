<?php

namespace ElliottLawson\Converse\Events;

use ElliottLawson\Converse\Models\Message;
use ElliottLawson\Converse\Models\MessageChunk;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChunkReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public MessageChunk $chunk
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chunk.received';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'chunk' => [
                'content' => $this->chunk->content,
                'sequence' => $this->chunk->sequence,
                'metadata' => $this->chunk->metadata,
            ],
        ];
    }
}
