# Broadcasting

Converse integrates seamlessly with Laravel Broadcasting to provide real-time updates for conversations, messages, and streaming responses.

## Setup

### Install Broadcasting Dependencies

First, install the necessary packages:

```bash
composer require pusher/pusher-php-server
npm install --save-dev laravel-echo pusher-js

# For React hooks support (optional)
npm install --save @laravel-echo/react
```

### Configure Broadcasting

Update your `.env` file:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

### Frontend Setup

#### React Setup (Recommended)

For React applications, wrap your app with the EchoProvider:

```jsx
// App.jsx
import { EchoProvider } from '@laravel-echo/react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    },
});

function App() {
    return (
        <EchoProvider echo={echo}>
            {/* Your app components */}
        </EchoProvider>
    );
}
```

#### Classic JavaScript Setup

For non-React applications:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true,
    auth: {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    },
});
```

## Broadcasting Events

### Creating Broadcast Events

Create events that will be broadcast to users:

```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ElliottLawson\Converse\Models\Message;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'role' => $this->message->role,
            'created_at' => $this->message->created_at->toIso8601String(),
            'metadata' => $this->message->metadata,
        ];
    }
}
```

### Streaming Updates

Broadcast streaming chunks in real-time:

```php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class StreamChunk implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public string $chunk,
        public int $sequence
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stream.chunk';
    }
}
```

## Channel Authorization

Define authorization logic for private channels:

```php
// routes/channels.php
use App\Models\User;
use ElliottLawson\Converse\Models\Conversation;

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    $conversation = Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    // Check if user owns the conversation
    if ($conversation->conversationable_type === User::class && 
        $conversation->conversationable_id === $user->id) {
        return true;
    }
    
    // Check if user has been granted access
    return $conversation->sharedWith()
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});
```

## Listening on the Frontend

### Using React Hooks (Recommended)

Laravel Echo 2.1+ provides React hooks for a more modern development experience:

```jsx
import { usePrivateChannel, useListen } from '@laravel-echo/react';

function ConversationComponent({ conversationId }) {
    // Subscribe to private channel
    const { channel } = usePrivateChannel(`conversation.${conversationId}`);
    
    // Listen for new messages
    useListen(channel, '.message.created', (e) => {
        console.log('New message:', e);
        appendMessageToUI(e);
    });
    
    // Listen for message updates
    useListen(channel, '.message.updated', (e) => {
        console.log('Message updated:', e);
        updateMessageInUI(e);
    });
    
    // Listen for message deletions
    useListen(channel, '.message.deleted', (e) => {
        console.log('Message deleted:', e);
        removeMessageFromUI(e.id);
    });
    
    return (
        // Your component JSX
    );
}
```

### Classic JavaScript Approach

If not using React hooks:

```javascript
// Join conversation channel
const conversationChannel = Echo.private(`conversation.${conversationId}`);

// Listen for new messages
conversationChannel.listen('.message.created', (e) => {
    console.log('New message:', e);
    appendMessageToUI(e);
});

// Listen for message updates
conversationChannel.listen('.message.updated', (e) => {
    console.log('Message updated:', e);
    updateMessageInUI(e);
});

// Listen for message deletions
conversationChannel.listen('.message.deleted', (e) => {
    console.log('Message deleted:', e);
    removeMessageFromUI(e.id);
});
```

### Streaming Updates

Handle real-time streaming with React hooks:

```jsx
import { usePrivateChannel, useListen } from '@laravel-echo/react';
import { useState } from 'react';

function StreamingConversation({ conversationId }) {
    const [streamingMessages, setStreamingMessages] = useState(new Map());
    const { channel } = usePrivateChannel(`conversation.${conversationId}`);
    
    // Listen for streaming chunks
    useListen(channel, '.stream.chunk', (e) => {
        setStreamingMessages(prev => {
            const updated = new Map(prev);
            const content = (updated.get(e.messageId) || '') + e.chunk;
            updated.set(e.messageId, content);
            return updated;
        });
    });
    
    // Listen for stream completion
    useListen(channel, '.stream.completed', (e) => {
        finalizeStreamingMessage(e.messageId);
        setStreamingMessages(prev => {
            const updated = new Map(prev);
            updated.delete(e.messageId);
            return updated;
        });
    });
    
    // Listen for stream failures
    useListen(channel, '.stream.failed', (e) => {
        markMessageAsFailed(e.messageId, e.error);
        setStreamingMessages(prev => {
            const updated = new Map(prev);
            updated.delete(e.messageId);
            return updated;
        });
    });
    
    return (
        // Your streaming UI
    );
}
```

Classic JavaScript approach:

```javascript
let streamingMessages = new Map();

conversationChannel.listen('.stream.chunk', (e) => {
    // Get or create message content
    let content = streamingMessages.get(e.messageId) || '';
    content += e.chunk;
    streamingMessages.set(e.messageId, content);
    
    // Update UI
    updateStreamingMessage(e.messageId, content);
});

conversationChannel.listen('.stream.completed', (e) => {
    // Mark message as complete
    finalizeStreamingMessage(e.messageId);
    streamingMessages.delete(e.messageId);
});

conversationChannel.listen('.stream.failed', (e) => {
    // Handle streaming failure
    markMessageAsFailed(e.messageId, e.error);
    streamingMessages.delete(e.messageId);
});
```

## Presence Channels

Track who's currently viewing a conversation:

```php
// Create a presence channel event
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;

class ConversationPresence implements ShouldBroadcast
{
    public function __construct(
        public int $conversationId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.' . $this->conversationId . '.presence'),
        ];
    }
}
```

Frontend presence handling with React hooks:

```jsx
import { usePresenceChannel, useWhisper } from '@laravel-echo/react';
import { useState } from 'react';

function ConversationPresence({ conversationId, currentUser }) {
    const [activeUsers, setActiveUsers] = useState([]);
    const [typingUsers, setTypingUsers] = useState(new Set());
    
    const { channel } = usePresenceChannel(
        `conversation.${conversationId}.presence`,
        {
            // Called with initial users
            here: (users) => {
                console.log('Users currently here:', users);
                setActiveUsers(users);
            },
            // Called when a user joins
            joining: (user) => {
                console.log('User joined:', user);
                setActiveUsers(prev => [...prev, user]);
            },
            // Called when a user leaves
            leaving: (user) => {
                console.log('User left:', user);
                setActiveUsers(prev => prev.filter(u => u.id !== user.id));
            },
            // Handle errors
            error: (error) => {
                console.error('Presence error:', error);
            }
        }
    );
    
    // Send typing indicators
    const whisper = useWhisper(channel);
    
    const handleTyping = () => {
        whisper('typing', {
            user: currentUser,
            timestamp: Date.now(),
        });
    };
    
    // Listen for typing indicators
    useWhisper(channel, 'typing', (e) => {
        setTypingUsers(prev => new Set(prev).add(e.user.id));
        
        // Remove after 3 seconds
        setTimeout(() => {
            setTypingUsers(prev => {
                const updated = new Set(prev);
                updated.delete(e.user.id);
                return updated;
            });
        }, 3000);
    });
    
    return (
        // Your presence UI
    );
}
```

Classic JavaScript approach:

```javascript
// Join presence channel
const presenceChannel = Echo.join(`conversation.${conversationId}.presence`);

presenceChannel
    .here((users) => {
        console.log('Users currently here:', users);
        updateActiveUsers(users);
    })
    .joining((user) => {
        console.log('User joined:', user);
        addActiveUser(user);
    })
    .leaving((user) => {
        console.log('User left:', user);
        removeActiveUser(user);
    })
    .error((error) => {
        console.error('Presence error:', error);
    });

// Whisper typing indicators
presenceChannel.whisper('typing', {
    user: currentUser,
    timestamp: Date.now(),
});

presenceChannel.listenForWhisper('typing', (e) => {
    showTypingIndicator(e.user);
    
    // Hide after 3 seconds
    setTimeout(() => hideTypingIndicator(e.user), 3000);
});
```

## Advanced Broadcasting Patterns

### Batch Updates

For high-frequency updates, batch them:

```php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Events\BatchedUpdates;

class BroadcastBatcher
{
    private string $key;
    private int $interval;
    
    public function __construct(string $key, int $interval = 100)
    {
        $this->key = $key;
        $this->interval = $interval;
    }
    
    public function add(string $channel, array $data): void
    {
        $batch = Cache::get($this->key, []);
        $batch[] = ['channel' => $channel, 'data' => $data];
        Cache::put($this->key, $batch, now()->addMilliseconds($this->interval));
        
        if (count($batch) === 1) {
            // Schedule flush
            dispatch(function () {
                $this->flush();
            })->delay(now()->addMilliseconds($this->interval));
        }
    }
    
    public function flush(): void
    {
        $batch = Cache::pull($this->key, []);
        
        if (!empty($batch)) {
            broadcast(new BatchedUpdates($batch));
        }
    }
}
```

### Queue Configuration

Optimize broadcasting performance:

```php
// config/queue.php
'connections' => [
    'broadcasting' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'broadcasting',
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => false,
    ],
],

// In your broadcast event
class StreamChunk implements ShouldBroadcastNow
{
    public function broadcastQueue(): string
    {
        return 'broadcasting';
    }
}
```

### Error Handling

Handle broadcasting failures gracefully:

```php
namespace App\Listeners;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;

class BroadcastEventListener
{
    public function handle($event): void
    {
        try {
            broadcast($event);
        } catch (BroadcastException $e) {
            Log::error('Broadcasting failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
            
            // Fallback: Store for polling
            $this->storeForPolling($event);
        }
    }
    
    private function storeForPolling($event): void
    {
        // Store event data for clients to poll
        Cache::push('pending_events:' . $event->userId, [
            'type' => get_class($event),
            'data' => $event->broadcastWith(),
            'timestamp' => now(),
        ]);
    }
}
```

## Testing Broadcasting

Test your broadcast events:

```php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;

public function test_message_creation_broadcasts_event()
{
    Event::fake();
    
    $conversation = $this->user->startConversation(['title' => 'Test']);
    $message = $conversation->addUserMessage('Hello');
    
    Event::assertDispatched(NewMessage::class, function ($event) use ($message) {
        return $event->message->id === $message->id;
    });
}

public function test_user_can_access_conversation_channel()
{
    $conversation = $this->user->startConversation(['title' => 'Test']);
    
    $result = Broadcast::channel('conversation.' . $conversation->id)
        ->check($this->user, $conversation->id);
        
    $this->assertTrue($result);
}
```

## Performance Tips

1. **Use ShouldBroadcastNow** for time-sensitive events to skip the queue
2. **Batch updates** when dealing with rapid changes
3. **Use presence channels** sparingly as they maintain state
4. **Implement fallback mechanisms** for when broadcasting fails
5. **Monitor queue performance** to ensure timely delivery

## Next Steps

- Implement [Real-time Chat](/examples/real-time) example
- Learn about [Events](/guide/events) that trigger broadcasts
- Explore [Streaming](/guide/streaming) implementation patterns 