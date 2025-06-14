# Events API Reference

Converse dispatches events throughout the conversation lifecycle, allowing you to hook into various actions.

## Available Events

### ConversationCreated

Dispatched when a new conversation is created.

**Properties:**
- `$conversation` - The created Conversation instance

**Broadcast Channel:** `private-user.{user_id}`  
**Broadcast Event:** `conversation.created`

### MessageCreated

Dispatched when a new message is added to a conversation.

**Properties:**
- `$message` - The created Message instance

**Broadcast Channel:** `private-conversation.{conversation_id}`  
**Broadcast Event:** `message.created`

### MessageUpdated

Dispatched when a message is updated (e.g., during streaming).

**Properties:**
- `$message` - The updated Message instance

**Broadcast Channel:** `private-conversation.{conversation_id}`  
**Broadcast Event:** `message.updated`

### MessageCompleted

Dispatched when a streaming message is completed.

**Properties:**
- `$message` - The completed Message instance

**Broadcast Channel:** `private-conversation.{conversation_id}`  
**Broadcast Event:** `message.completed`

### ChunkReceived

Dispatched when a new chunk is added to a streaming message.

**Properties:**
- `$message` - The parent Message instance
- `$chunk` - The MessageChunk instance

**Broadcast Channel:** `private-conversation.{conversation_id}`  
**Broadcast Event:** `chunk.received`

## Broadcasting Configuration

All events implement `ShouldBroadcast` and are ready for real-time features.

### Broadcast Channels

| Event | Channel | Event Name |
|-------|---------|------------|
| ConversationCreated | private-user.{user_id} | conversation.created |
| MessageCreated | private-conversation.{conversation_id} | message.created |
| MessageUpdated | private-conversation.{conversation_id} | message.updated |
| MessageCompleted | private-conversation.{conversation_id} | message.completed |
| ChunkReceived | private-conversation.{conversation_id} | chunk.received |

### Frontend Integration

```jsx
import { useEcho } from '@laravel/echo-react';

// Listen for conversation events
useEcho(
    `private-conversation.${conversationId}`,
    'message.created',
    (e) => {
        console.log('New message:', e.message);
    }
);

// Listen for streaming chunks
useEcho(
    `private-conversation.${conversationId}`,
    'chunk.received',
    (e) => {
        console.log('New chunk:', e.chunk);
    }
);
```

## Event Payload Structure

### MessageCreated Payload

```json
{
    "message": {
        "id": 123,
        "conversation_id": 456,
        "role": "assistant",
        "content": "Hello! How can I help you?",
        "status": "success",
        "is_complete": true,
        "metadata": {},
        "created_at": "2024-01-01T12:00:00Z"
    }
}
```

### ChunkReceived Payload

```json
{
    "message_id": 123,
    "chunk": {
        "id": 789,
        "content": "Hello",
        "sequence": 0,
        "metadata": {}
    }
}
``` 