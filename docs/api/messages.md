# Messages API Reference

The `Message` model represents individual messages within a conversation.

## Model Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Unique identifier |
| `uuid` | uuid | Unique UUID identifier |
| `conversation_id` | integer | Parent conversation ID |
| `role` | MessageRole | Message role (user, assistant, system, tool_call, tool_result) |
| `content` | text | Message content |
| `metadata` | json | Additional metadata |
| `status` | MessageStatus | Message status (pending, success, error) |
| `is_complete` | boolean | Whether streaming is complete |
| `completed_at` | timestamp | Completion timestamp |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |
| `deleted_at` | timestamp | Soft delete timestamp |

## Available Methods

### Relationships

```php
// Get the parent conversation
$conversation = $message->conversation;

// Get message chunks (for streaming)
$chunks = $message->chunks;

// Get attachments
$attachments = $message->attachments;
```

### Query Scopes

```php
// Filter by role
Message::byRole(MessageRole::User)->get();
Message::user()->get();
Message::assistant()->get();
Message::system()->get();

// Filter by status
Message::completed()->get();
Message::streaming()->get();
Message::failed()->get();
```

### Streaming Methods

```php
// Append a chunk during streaming
$chunk = $message->appendChunk('Hello ', ['index' => 0]);

// Complete streaming
$message->completeStreaming(['tokens' => 150]);

// Fail streaming with error
$message->failStreaming('API rate limit exceeded', ['code' => 429]);
```

### Error Handling

```php
// Mark message as error
$message->markAsError('Invalid API key', ['code' => 401]);
```

### Tool Methods

```php
// Check if message is a tool call
if ($message->isToolCall()) {
    $toolName = $message->getToolName();
    $toolCallId = $message->getToolCallId();
}

// Check if message is a tool result
if ($message->isToolResult()) {
    // Handle tool result
}
```

## Usage Examples

### Creating Messages

```php
// Through conversation (recommended)
$message = $conversation->addUserMessage('Hello, AI!');

// Direct creation
$message = Message::create([
    'conversation_id' => $conversation->id,
    'role' => MessageRole::User,
    'content' => 'Hello, AI!',
    'metadata' => ['user_id' => auth()->id()],
]);
```

### Working with Streaming

```php
// Start a streaming message
$message = $conversation->createAssistantMessage('', [
    'is_complete' => false,
    'status' => MessageStatus::Pending,
]);

// Stream chunks
foreach ($streamedChunks as $chunk) {
    $message->appendChunk($chunk);
}

// Complete the stream
$message->completeStreaming(['total_tokens' => 500]);
```

### Querying Messages

```php
// Get all user messages from a conversation
$userMessages = $conversation->messages()
    ->user()
    ->completed()
    ->get();

// Get failed messages
$failedMessages = $conversation->messages()
    ->failed()
    ->with('chunks')
    ->get();

// Get recent assistant responses
$recentResponses = Message::assistant()
    ->completed()
    ->where('created_at', '>', now()->subHours(24))
    ->latest()
    ->take(10)
    ->get();
```

## Events

The Message model dispatches the following events:

- `MessageCreated` - When a message is created
- `MessageUpdated` - When a message is updated
- `MessageCompleted` - When streaming is completed
- `ChunkReceived` - When a new chunk is appended 