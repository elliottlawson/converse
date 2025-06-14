# Messages

Messages are the core building blocks of conversations in Laravel Converse. Each message represents a single interaction in the conversation history.

## Message Types

Laravel Converse supports all standard AI conversation message types:

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

## Fluent vs Direct API

Laravel Converse provides two ways to work with messages:

### Fluent API (Chaining)

Perfect for building conversation flows:

```php
$conversation
    ->addSystemMessage('You are helpful')
    ->addUserMessage('Hello')
    ->addAssistantMessage('Hi there!');
```

### Direct API (Immediate Access)

When you need the message object immediately:

```php
$message = $conversation->createUserMessage('Hello');
$messageId = $message->id;
$messageContent = $message->content;

// Update message metadata
$message->update(['metadata' => ['edited' => true]]);
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

## Message Ordering

Messages are automatically ordered by creation time:

```php
// Messages are ordered oldest to newest by default
foreach ($conversation->messages as $message) {
    echo "{$message->role}: {$message->content}\n";
}

// Get messages in reverse order
$recentFirst = $conversation->messages()->latest()->get();
```

## Bulk Operations

For performance when adding many messages:

```php
$messages = [
    ['role' => 'system', 'content' => 'You are helpful'],
    ['role' => 'user', 'content' => 'Hello'],
    ['role' => 'assistant', 'content' => 'Hi!'],
];

$conversation->messages()->createMany($messages);
``` 