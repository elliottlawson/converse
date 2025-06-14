# Messages

Messages are the core building blocks of conversations in Converse. Each message represents a single interaction in the conversation history.

## Understanding Messages

A Message is a standard Eloquent model that can be queried and manipulated just like any other Laravel model. We've enhanced it with helpful methods and scopes specifically designed for AI conversations, but at its core, it's a regular model you already know how to work with.

## Two Ways to Work with Messages

Converse provides two distinct approaches for adding messages to conversations:

### Fluent API (Returns Conversation)

The `add*Message` methods return the conversation instance, allowing you to chain multiple operations:

```php
$conversation = $conversation
    ->addSystemMessage('You are helpful')
    ->addUserMessage('Hello')
    ->addAssistantMessage('Hi there!');
```

Use this when you're building a conversation flow and don't need immediate access to the message objects.

### Message Creation API (Returns Message)

The `create*Message` methods return the actual Message model instance:

```php
$message = $conversation->createUserMessage('Hello');
$messageId = $message->id;
$content = $message->content;
```

Use this when you need to work with the message immediately after creation (e.g., getting its ID, updating metadata, etc.).

## Message Types

Converse supports all standard AI conversation message types:

### User Messages

User messages represent input from the end user:

```php
$conversation->addUserMessage('How do I deploy Laravel to production?');
```

### Assistant Messages

Assistant messages are responses from the AI:

```php
$conversation->addAssistantMessage('There are several ways to deploy Laravel...');
```

### System Messages

System messages provide context or instructions to the AI:

```php
$conversation->addSystemMessage('You are a helpful Laravel deployment expert.');
```

### Tool Messages

For AI providers that support function/tool calling:

```php
// Tool call (from assistant)
$conversation->addToolCallMessage('search_docs(query="Laravel deployment")');

// Tool result (system provides the result)
$conversation->addToolResultMessage(json_encode([
    'results' => [
        ['title' => 'Laravel Forge', 'url' => '...'],
        ['title' => 'Laravel Vapor', 'url' => '...'],
    ]
]));
```

## Message Metadata

Every message can include metadata for additional context:

```php
$conversation->addUserMessage('Deploy my app', [
    'user_agent' => $request->userAgent(),
    'ip_address' => $request->ip(),
    'timestamp' => now()->toIso8601String(),
]);
```



## Retrieving Messages

Access messages through the relationship:

```php
// Get all messages
$messages = $conversation->messages;

// Get messages by role
$userMessages = $conversation->messages()->where('role', 'user')->get();

// Get latest message
$lastMessage = $conversation->messages()->latest()->first();

// Paginate messages
$messages = $conversation->messages()->paginate(20);
```

## Helper Methods

Converse provides convenient helper methods for common operations:

```php
// Get the most recently added message
$lastMessage = $conversation->getLastMessage();

// Get recent messages as a collection
$recentMessages = $conversation->getRecentMessages(5);

// Create a conversation subset with recent messages (useful for context windows)
$subset = $conversation->selectRecentMessages(10);
```

## Message Ordering

Messages are automatically ordered by creation time:

```php
// Messages are ordered oldest to newest by default
$formatted = $conversation->messages
    ->map(fn($message) => "{$message->role}: {$message->content}")
    ->join("\n");

// Get messages in reverse order
$recentFirst = $conversation->messages()->latest()->get();
```

## Bulk Operations

For performance when adding many messages:

```php
$conversation->messages()->createMany([
    ['role' => 'system', 'content' => 'You are helpful'],
    ['role' => 'user', 'content' => 'Hello'],
    ['role' => 'assistant', 'content' => 'Hi!'],
]);
``` 