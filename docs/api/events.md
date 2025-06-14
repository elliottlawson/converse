# Events API Reference

Converse dispatches events throughout the conversation lifecycle, allowing you to hook into various actions and build features like analytics, logging, or real-time updates.

## Available Events

### ConversationCreated

Dispatched when a new conversation is created.

```php
use ElliottLawson\Converse\Events\ConversationCreated;

class ConversationEventListener
{
    public function handle(ConversationCreated $event)
    {
        $conversation = $event->conversation;
        
        // Log analytics
        Analytics::track('conversation.started', [
            'user_id' => $conversation->conversable_id,
            'title' => $conversation->title,
        ]);
    }
}
```

### MessageCreated

Dispatched when a new message is added to a conversation.

```php
use ElliottLawson\Converse\Events\MessageCreated;

class MessageEventListener
{
    public function handle(MessageCreated $event)
    {
        $message = $event->message;
        
        // Track token usage
        if ($message->role->value === 'assistant') {
            TokenUsage::increment(
                $message->conversation->conversable,
                $message->metadata['tokens'] ?? 0
            );
        }
    }
}
```

### MessageUpdated

Dispatched when a message is updated (e.g., during streaming).

```php
public function handle(MessageUpdated $event)
{
    $message = $event->message;
    
    // Update UI in real-time
    broadcast(new MessageStreamUpdate($message));
}
```

### MessageCompleted

Dispatched when a streaming message is completed.

```php
public function handle(MessageCompleted $event)
{
    $message = $event->message;
    
    // Calculate costs
    $cost = CostCalculator::calculate(
        $message->metadata['model'],
        $message->metadata['tokens']
    );
    
    // Store for billing
    Usage::create([
        'user_id' => $message->conversation->conversable_id,
        'tokens' => $message->metadata['tokens'],
        'cost' => $cost,
    ]);
}
```

### ChunkReceived

Dispatched when a new chunk is added to a streaming message.

```php
public function handle(ChunkReceived $event)
{
    $message = $event->message;
    $chunk = $event->chunk;
    
    // Broadcast chunk to frontend
    broadcast(new StreamingChunk(
        $message->conversation_id,
        $chunk->content
    ))->toOthers();
}
```

## Broadcasting Configuration

All events implement `ShouldBroadcast` and are ready for real-time features:

```php
// In your frontend (using Laravel Echo)
Echo.private(`conversation.${conversationId}`)
    .listen('.message.created', (e) => {
        console.log('New message:', e.message);
    })
    .listen('.chunk.received', (e) => {
        console.log('New chunk:', e.chunk);
    });
```

### Broadcast Channels

| Event | Channel | Event Name |
|-------|---------|------------|
| ConversationCreated | private-user.{user_id} | conversation.created |
| MessageCreated | private-conversation.{conversation_id} | message.created |
| MessageUpdated | private-conversation.{conversation_id} | message.updated |
| MessageCompleted | private-conversation.{conversation_id} | message.completed |
| ChunkReceived | private-conversation.{conversation_id} | chunk.received |

## Registering Listeners

Register your event listeners in `EventServiceProvider`:

```php
protected $listen = [
    ConversationCreated::class => [
        LogConversationStart::class,
        NotifyAdmins::class,
    ],
    MessageCreated::class => [
        TrackTokenUsage::class,
        CheckContentModeration::class,
    ],
    MessageCompleted::class => [
        CalculateCosts::class,
        UpdateUserStats::class,
    ],
    ChunkReceived::class => [
        BroadcastToWebsocket::class,
    ],
];
```

## Example: Building Analytics

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\MessageCreated;
use App\Models\ConversationAnalytics;

class TrackConversationMetrics
{
    public function handle(MessageCreated $event)
    {
        $message = $event->message;
        $conversation = $message->conversation;
        
        ConversationAnalytics::updateOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'message_count' => $conversation->messages()->count(),
                'last_message_at' => now(),
                'total_tokens' => $conversation->messages()
                    ->whereJsonContains('metadata->tokens', '>', 0)
                    ->sum('metadata->tokens'),
            ]
        );
    }
}
```

## Example: Real-time Chat UI

```php
// Backend: Automatically broadcasts via ChunkReceived event

// Frontend: React component
function ChatMessage({ conversationId }) {
    const [messages, setMessages] = useState([]);
    
    useEffect(() => {
        // Listen for new complete messages
        Echo.private(`conversation.${conversationId}`)
            .listen('.message.created', (e) => {
                setMessages(prev => [...prev, e.message]);
            });
            
        // Listen for streaming chunks
        Echo.private(`conversation.${conversationId}`)
            .listen('.chunk.received', (e) => {
                setMessages(prev => {
                    const last = prev[prev.length - 1];
                    if (last?.id === e.message_id) {
                        last.content += e.chunk.content;
                        return [...prev.slice(0, -1), last];
                    }
                    return prev;
                });
            });
    }, [conversationId]);
    
    return (
        <div>{messages.map(msg => 
            <Message key={msg.id} {...msg} />
        )}</div>
    );
}
``` 