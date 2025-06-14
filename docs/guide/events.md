# Events

Converse dispatches events throughout the conversation lifecycle, perfect for real-time updates, analytics, and integrations.

## Available Events

The package dispatches the following events:

- `ConversationCreated` - When a new conversation is created
- `MessageCreated` - When a new message is added
- `ChunkReceived` - When a streaming chunk is received
- `MessageCompleted` - When a streaming message completes
- `MessageFailed` - When a streaming message fails

## Setting Up Event Listeners

Register your event listeners in the `EventServiceProvider`:

```php
use ElliottLawson\Converse\Events\ConversationCreated;
use ElliottLawson\Converse\Events\MessageCreated;
use ElliottLawson\Converse\Events\ChunkReceived;
use ElliottLawson\Converse\Events\MessageCompleted;

// In your EventServiceProvider
protected $listen = [
    ConversationCreated::class => [
        SendWelcomeMessage::class,
        TrackConversationAnalytics::class,
    ],
    MessageCreated::class => [
        BroadcastMessageToUser::class,
        CheckForModeration::class,
    ],
    ChunkReceived::class => [
        StreamChunkToWebSocket::class,
    ],
    MessageCompleted::class => [
        CalculateCosts::class,
        UpdateUserCredits::class,
    ],
];
```

## Example Listeners

### Broadcasting Messages

Broadcast new messages to users in real-time:

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\MessageCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\NewMessage;

class BroadcastMessageToUser implements ShouldQueue
{
    public function handle(MessageCreated $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;
        
        // Broadcast to user's private channel
        broadcast(new NewMessage($message))
            ->toOthers()
            ->onChannel("user.{$conversation->conversable_id}");
    }
}
```

### Streaming Chunks

Handle real-time streaming updates:

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\ChunkReceived;
use App\Events\StreamUpdate;

class StreamChunkToWebSocket
{
    public function handle(ChunkReceived $event): void
    {
        $chunk = $event->chunk;
        $message = $chunk->message;
        
        // Stream to conversation channel
        broadcast(new StreamUpdate(
            conversationId: $message->conversation_id,
            messageId: $message->id,
            chunk: $chunk->content,
            sequence: $chunk->sequence
        ))->toOthers();
    }
}
```

### Calculating Costs

Track token usage and calculate costs:

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\MessageCompleted;

class CalculateCosts
{
    public function handle(MessageCompleted $event): void
    {
        $message = $event->message;
        
        if ($message->role->value === 'assistant' && isset($message->metadata['tokens'])) {
            $cost = $this->calculateTokenCost(
                $message->metadata['prompt_tokens'] ?? 0,
                $message->metadata['completion_tokens'] ?? 0,
                $message->metadata['model'] ?? 'gpt-3.5-turbo'
            );
            
            // Store cost for billing
            $message->conversation->conversable->billing()->create([
                'tokens_used' => $message->metadata['tokens'],
                'cost' => $cost,
                'model' => $message->metadata['model'],
            ]);
        }
    }
    
    private function calculateTokenCost($promptTokens, $completionTokens, $model): float
    {
        $rates = [
            'gpt-3.5-turbo' => [
                'prompt' => 0.0015 / 1000,
                'completion' => 0.002 / 1000,
            ],
            'gpt-4' => [
                'prompt' => 0.03 / 1000,
                'completion' => 0.06 / 1000,
            ],
        ];
        
        $rate = $rates[$model] ?? $rates['gpt-3.5-turbo'];
        
        return ($promptTokens * $rate['prompt']) + ($completionTokens * $rate['completion']);
    }
}
```

### Content Moderation

Check messages for inappropriate content:

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\MessageCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\ModerationService;

class CheckForModeration implements ShouldQueue
{
    public function __construct(
        private ModerationService $moderation
    ) {}
    
    public function handle(MessageCreated $event): void
    {
        $message = $event->message;
        
        // Only check user messages
        if ($message->role->value !== 'user') {
            return;
        }
        
        $result = $this->moderation->check($message->content);
        
        if ($result->flagged) {
            $message->update([
                'metadata' => array_merge($message->metadata ?? [], [
                    'moderation' => [
                        'flagged' => true,
                        'categories' => $result->categories,
                        'scores' => $result->scores,
                    ]
                ])
            ]);
            
            // Optionally delete or hide the message
            if ($result->severity === 'high') {
                $message->delete();
            }
        }
    }
}
```

## Broadcasting Configuration

Set up broadcasting for real-time features:

```php
// config/broadcasting.php
'channels' => [
    'user.{userId}' => function ($user, $userId) {
        return (int) $user->id === (int) $userId;
    },
    
    'conversation.{conversationId}' => function ($user, $conversationId) {
        return $user->conversations()
            ->where('id', $conversationId)
            ->exists();
    },
],
```

## Frontend Integration

### Using React Hooks

Listen for events using Laravel Echo React hooks:

```jsx
import { usePrivateChannel, useListen } from '@laravel-echo/react';
import { useState } from 'react';

function ConversationComponent({ userId, conversationId }) {
    const [messages, setMessages] = useState([]);
    const [streamingMessages, setStreamingMessages] = useState(new Map());
    
    // User channel for personal notifications
    const { channel: userChannel } = usePrivateChannel(`user.${userId}`);
    
    // Conversation channel for real-time updates
    const { channel: conversationChannel } = usePrivateChannel(`conversation.${conversationId}`);
    
    // Listen for new messages
    useListen(userChannel, 'NewMessage', (e) => {
        console.log('New message:', e.message);
        setMessages(prev => [...prev, e.message]);
    });
    
    // Listen for streaming updates
    useListen(conversationChannel, 'StreamUpdate', (e) => {
        setStreamingMessages(prev => {
            const updated = new Map(prev);
            const current = updated.get(e.messageId) || '';
            updated.set(e.messageId, current + e.chunk);
            return updated;
        });
    });
    
    // Listen for completion
    useListen(conversationChannel, 'MessageCompleted', (e) => {
        // Move from streaming to completed
        const content = streamingMessages.get(e.messageId);
        setMessages(prev => [...prev, { id: e.messageId, content, completed: true }]);
        setStreamingMessages(prev => {
            const updated = new Map(prev);
            updated.delete(e.messageId);
            return updated;
        });
    });
    
    return (
        // Your UI components
    );
}
```

### Classic JavaScript Approach

```javascript
// Listen for new messages
Echo.private(`user.${userId}`)
    .listen('NewMessage', (e) => {
        console.log('New message:', e.message);
        // Update UI
    });

// Listen for streaming updates
Echo.private(`conversation.${conversationId}`)
    .listen('StreamUpdate', (e) => {
        // Append chunk to message
        appendChunkToMessage(e.messageId, e.chunk);
    })
    .listen('MessageCompleted', (e) => {
        // Mark message as complete
        markMessageComplete(e.messageId);
    });
```



## Next Steps

- Implement [Real-time Updates](/examples/real-time) in your application
- Learn about [Broadcasting](/guide/broadcasting) setup
- Explore [Streaming](/guide/streaming) with events 