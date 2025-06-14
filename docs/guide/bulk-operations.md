# Bulk Operations

Laravel Converse provides several methods for efficiently working with multiple messages at once, whether you're importing conversation history, migrating from other systems, or building conversation context.

## Helper Methods

These helper methods make it easy to work with messages:

```php
// Get the most recently added message
$lastMessage = $conversation->getLastMessage();

// Get recent messages as a collection
$recentMessages = $conversation->getRecentMessages(5);

// Create a conversation subset with recent messages (useful for context windows)
$subset = $conversation->selectRecentMessages(10);
```

## Bulk Importing Messages

The `addMessages()` method allows you to add multiple messages at once, perfect for importing conversation history or migrating from other systems.

### Using Message DTOs

The most type-safe approach uses message Data Transfer Objects:

```php
use ElliottLawson\Converse\Messages\UserMessage;
use ElliottLawson\Converse\Messages\AssistantMessage;
use ElliottLawson\Converse\Messages\SystemMessage;

// Add multiple messages using DTOs
$messages = $conversation->addMessages([
    new SystemMessage('You are a Laravel expert'),
    new UserMessage('How do I deploy to production?'),
    new AssistantMessage('Let me help you with deployment...'),
]);

// With metadata in DTOs
$messages = $conversation->addMessages([
    new UserMessage('Analyze this code', [
        'timestamp' => now(), 
        'ip' => request()->ip()
    ]),
    new AssistantMessage('I found several issues...', [
        'model' => 'gpt-4', 
        'tokens' => 245
    ]),
]);
```

### Simple String Messages

For quick user messages, you can pass strings directly:

```php
// Pass multiple arguments directly (all treated as user messages)
$messages = $conversation->addMessages(
    'Hello',
    'I need help with Laravel',
    'How do I set up queues?'
);

// Or pass an array of strings
$messages = $conversation->addMessages([
    'Hello',
    'I need help with Laravel',
    'How do I set up queues?',
]);
```

### Using Role Arrays

You can specify roles using arrays with the MessageRole enum or strings:

```php
use ElliottLawson\Converse\Enums\MessageRole;

// With roles using enum directly
$messages = $conversation->addMessages([
    ['role' => MessageRole::System, 'content' => 'You are a Laravel expert'],
    ['role' => MessageRole::User, 'content' => 'How do I deploy to production?'],
    ['role' => MessageRole::Assistant, 'content' => 'Let me help you with deployment...'],
]);

// With roles as strings (automatically converted to enum)
$messages = $conversation->addMessages([
    ['role' => 'system', 'content' => 'You are a Laravel expert'],
    ['role' => 'user', 'content' => 'How do I deploy to production?'],
    ['role' => 'assistant', 'content' => 'Let me help you with deployment...'],
]);

// Including metadata
$messages = $conversation->addMessages([
    [
        'role' => 'user', 
        'content' => 'Analyze this code',
        'metadata' => ['source' => 'web_ui', 'session_id' => 'abc123']
    ],
    [
        'role' => 'assistant',
        'content' => 'Here is my analysis...',
        'metadata' => ['model' => 'gpt-4', 'tokens' => 532]
    ],
]);
```

## Practical Examples

### Importing from Another System

```php
// Migrate conversations from legacy system
$legacyMessages = DB::table('old_chat_messages')
    ->where('chat_id', $oldChatId)
    ->orderBy('created_at')
    ->get();

$messageDTOs = $legacyMessages->map(function ($msg) {
    return match($msg->sender_type) {
        'user' => new UserMessage($msg->text, [
            'legacy_id' => $msg->id,
            'created_at' => $msg->created_at,
        ]),
        'bot' => new AssistantMessage($msg->text, [
            'legacy_id' => $msg->id,
            'model' => $msg->bot_model,
        ]),
        'system' => new SystemMessage($msg->text),
    };
})->toArray();

$conversation->addMessages($messageDTOs);
```

### Building Context from Templates

```php
// Load context messages from configuration
$contextMessages = collect(config('ai.context_messages'))->map(function ($msg) {
    return new SystemMessage(
        view($msg['template'], $msg['data'] ?? [])->render()
    );
})->toArray();

$conversation->addMessages($contextMessages);
```

### Importing from JSON

```php
// Import from JSON export
$jsonData = json_decode(file_get_contents('conversation-export.json'), true);

$messages = collect($jsonData['messages'])->map(function ($msg) {
    $class = match($msg['role']) {
        'user' => UserMessage::class,
        'assistant' => AssistantMessage::class,
        'system' => SystemMessage::class,
        'tool_call' => ToolCallMessage::class,
        'tool_result' => ToolResultMessage::class,
    };
    
    return new $class($msg['content'], $msg['metadata'] ?? []);
})->toArray();

$conversation->addMessages($messages);
```

## Performance Considerations

### Database Efficiency

The `addMessages()` method uses Laravel's `insert()` for efficiency when adding multiple messages:

```php
// Efficient - single database query
$conversation->addMessages($largeArrayOfMessages);

// Less efficient - multiple database queries
foreach ($largeArrayOfMessages as $message) {
    $conversation->addUserMessage($message);
}
```

### Memory Usage

When importing very large conversations, consider chunking:

```php
collect($messages)->chunk(100)->each(function ($chunk) use ($conversation) {
    $conversation->addMessages($chunk->toArray());
});
```

## Working with Metadata

Metadata allows you to store provider-specific information, request details, or any custom data alongside messages:

```php
// Track user context
$conversation->addUserMessage('What about costs?', [
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'session_id' => session()->getId(),
]);

// Store AI provider details
$conversation->addAssistantMessage('Here are the pricing details...', [
    'model' => 'gpt-4-turbo',
    'temperature' => 0.7,
    'max_tokens' => 500,
    'prompt_tokens' => 85,
    'completion_tokens' => 150,
    'total_cost' => 0.0234,
]);

// Function call metadata
$conversation->addToolCallMessage('search_products(query="laptops")', [
    'function_name' => 'search_products',
    'arguments' => ['query' => 'laptops'],
    'call_id' => 'call_abc123',
]);
```

### Querying by Metadata

```php
// Find messages with specific metadata
$highCostMessages = $conversation->messages()
    ->where('metadata->total_cost', '>', 0.10)
    ->get();

// Messages from a specific model
$gpt4Messages = $conversation->messages()
    ->where('metadata->model', 'gpt-4')
    ->get();

// Group messages by session
$sessions = $conversation->messages()
    ->whereNotNull('metadata->session_id')
    ->get()
    ->groupBy('metadata.session_id');
```

## Best Practices

1. **Use DTOs for type safety**: When importing from external sources, DTOs provide validation and type safety
2. **Include metadata**: Always include relevant metadata for debugging and analytics
3. **Maintain order**: Ensure messages are added in chronological order for coherent conversations
4. **Validate before importing**: Check message content and roles before bulk importing
5. **Use transactions**: Wrap large imports in database transactions for data integrity

```php
DB::transaction(function () use ($conversation, $messages) {
    $conversation->addMessages($messages);
    $conversation->update(['imported_at' => now()]);
});
```

## Next Steps

- Learn about [Streaming Responses](/guide/streaming) for real-time message handling
- Explore [Events](/guide/events) fired during bulk operations
- See [Advanced Usage](/guide/advanced) for more complex scenarios 