# Real-time Updates Example

Learn how to implement real-time conversation updates using Laravel Broadcasting.

## Overview

This example demonstrates a complete real-time chat implementation with streaming responses, presence tracking, and typing indicators.

## Backend Setup

### Event Classes

Create events for real-time updates:

```php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use ElliottLawson\Converse\Models\Message;

class MessageCreated implements ShouldBroadcastNow
{
    public function __construct(
        public Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'role' => $this->message->role->value,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}

class StreamingChunk implements ShouldBroadcastNow
{
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
}
```

### Controller

```php
namespace App\Http\Controllers;

use ElliottLawson\Converse\Models\Conversation;
use App\Events\MessageCreated;
use App\Events\StreamingChunk;
use OpenAI\Laravel\Facades\OpenAI;

class RealtimeChatController extends Controller
{
    public function sendMessage(Request $request, Conversation $conversation)
    {
        // Add user message
        $userMessage = $conversation->addUserMessage($request->input('content'));
        broadcast(new MessageCreated($userMessage));

        // Start streaming assistant response
        $assistantMessage = $conversation->startStreamingAssistant();
        broadcast(new MessageCreated($assistantMessage));

        // Stream response from AI
        $this->streamResponse($conversation, $assistantMessage);

        return response()->json(['message' => $userMessage]);
    }

    private function streamResponse(Conversation $conversation, Message $message)
    {
        $messages = $conversation->messages->map(fn($m) => [
            'role' => $m->role->value,
            'content' => $m->content,
        ])->toArray();

        $stream = OpenAI::chat()->createStreamed([
            'model' => 'gpt-4',
            'messages' => $messages,
            'stream' => true,
        ]);

        $sequence = 0;
        foreach ($stream as $response) {
            if (isset($response->choices[0]->delta->content)) {
                $chunk = $response->choices[0]->delta->content;
                
                // Store chunk
                $message->appendChunk($chunk);
                
                // Broadcast chunk
                broadcast(new StreamingChunk(
                    $conversation->id,
                    $message->id,
                    $chunk,
                    $sequence++
                ));
            }
        }

        $message->completeStreaming();
    }
}
```

## Frontend Integration

### React Implementation with Hooks

```jsx
import React, { useState, useCallback, useRef, useEffect } from 'react';
import { usePrivateChannel, useListen, usePresenceChannel, useWhisper } from '@laravel-echo/react';

function RealtimeChat({ conversationId, currentUser }) {
    const [messages, setMessages] = useState([]);
    const [streamingContent, setStreamingContent] = useState(new Map());
    const [typingUsers, setTypingUsers] = useState(new Set());
    const [activeUsers, setActiveUsers] = useState([]);
    const inputRef = useRef(null);
    
    // Subscribe to conversation channel
    const { channel } = usePrivateChannel(`conversation.${conversationId}`);
    
    // Subscribe to presence channel
    const { channel: presenceChannel } = usePresenceChannel(
        `conversation.${conversationId}.presence`,
        {
            here: (users) => setActiveUsers(users),
            joining: (user) => setActiveUsers(prev => [...prev, user]),
            leaving: (user) => setActiveUsers(prev => prev.filter(u => u.id !== user.id)),
        }
    );
    
    // Listen for new messages
    useListen(channel, 'MessageCreated', (e) => {
        setMessages(prev => [...prev, {
            id: e.id,
            content: e.content,
            role: e.role,
            created_at: e.created_at,
            isStreaming: e.role === 'assistant' && !e.content
        }]);
    });
    
    // Listen for streaming chunks
    useListen(channel, 'StreamingChunk', (e) => {
        setStreamingContent(prev => {
            const updated = new Map(prev);
            const current = updated.get(e.messageId) || '';
            updated.set(e.messageId, current + e.chunk);
            return updated;
        });
    });
    
    // Listen for typing indicators
    const whisper = useWhisper(presenceChannel);
    
    useWhisper(presenceChannel, 'typing', (e) => {
        if (e.user.id !== currentUser.id) {
            setTypingUsers(prev => new Set(prev).add(e.user.id));
            setTimeout(() => {
                setTypingUsers(prev => {
                    const updated = new Set(prev);
                    updated.delete(e.user.id);
                    return updated;
                });
            }, 3000);
        }
    });
    
    // Send typing indicator
    const handleTyping = useCallback(() => {
        whisper('typing', { user: currentUser });
    }, [whisper, currentUser]);
    
    // Send message
    const sendMessage = useCallback(async () => {
        const content = inputRef.current.value.trim();
        if (!content) return;
        
        inputRef.current.value = '';
        
        try {
            await fetch(`/api/conversations/${conversationId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getAuthToken()}`
                },
                body: JSON.stringify({ content })
            });
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    }, [conversationId]);
    
    return (
        <div className="chat-container">
            <div className="active-users">
                {activeUsers.map(user => (
                    <span key={user.id} className="user-avatar">
                        {user.name.charAt(0)}
                    </span>
                ))}
            </div>
            
            <div className="messages">
                {messages.map(message => (
                    <div key={message.id} className={`message ${message.role}`}>
                        {message.isStreaming ? (
                            <span>{streamingContent.get(message.id) || ''}●●●</span>
                        ) : (
                            message.content
                        )}
                    </div>
                ))}
                
                {typingUsers.size > 0 && (
                    <div className="typing-indicator">
                        {typingUsers.size} user(s) typing...
                    </div>
                )}
            </div>
            
            <div className="input-area">
                <input
                    ref={inputRef}
                    type="text"
                    placeholder="Type a message..."
                    onKeyPress={(e) => {
                        handleTyping();
                        if (e.key === 'Enter') sendMessage();
                    }}
                />
                <button onClick={sendMessage}>Send</button>
            </div>
        </div>
    );
}
```

### Classic JavaScript Implementation

```javascript
// Initialize connection
const conversationChannel = Echo.private(`conversation.${conversationId}`);
const presenceChannel = Echo.join(`conversation.${conversationId}.presence`);

let messages = [];
let streamingContent = new Map();
let typingUsers = new Set();

// Handle presence
presenceChannel
    .here((users) => updateActiveUsers(users))
    .joining((user) => addActiveUser(user))
    .leaving((user) => removeActiveUser(user));

// Listen for messages
conversationChannel
    .listen('MessageCreated', (e) => {
        messages.push(e);
        renderMessage(e);
    })
    .listen('StreamingChunk', (e) => {
        const current = streamingContent.get(e.messageId) || '';
        streamingContent.set(e.messageId, current + e.chunk);
        updateStreamingMessage(e.messageId);
    });

// Handle typing
presenceChannel.listenForWhisper('typing', (e) => {
    showTypingIndicator(e.user);
});

// Send typing indicator
function handleTyping() {
    presenceChannel.whisper('typing', { user: currentUser });
}

// Send message
async function sendMessage() {
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    
    if (!content) return;
    
    input.value = '';
    
    try {
        await fetch(`/api/conversations/${conversationId}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`
            },
            body: JSON.stringify({ content })
        });
    } catch (error) {
        console.error('Failed to send message:', error);
    }
}
```

## Channel Authorization

```php
// routes/channels.php
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()->where('id', $conversationId)->exists();
});

Broadcast::channel('conversation.{conversationId}.presence', function ($user, $conversationId) {
    if ($user->conversations()->where('id', $conversationId)->exists()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar_url,
        ];
    }
});
```

## Next Steps

- Add message reactions functionality
- Implement file uploads with progress tracking
- Create voice messages with real-time transcription 