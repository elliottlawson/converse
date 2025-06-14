# Conversations API Reference

Complete API documentation for the Conversation model and methods.

## Model Properties

- `id` - Unique identifier
- `conversationable_type` - Polymorphic relation type
- `conversationable_id` - Polymorphic relation ID
- `title` - Conversation title
- `metadata` - JSON metadata field
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp
- `deleted_at` - Soft delete timestamp

## Methods

### Message Creation

```php
addUserMessage(string $content, array $metadata = []): Conversation
addAssistantMessage(string $content, array $metadata = []): Conversation
addSystemMessage(string $content, array $metadata = []): Conversation
addToolCallMessage(string $content, array $metadata = []): Conversation
addToolResultMessage(string $content, array $metadata = []): Conversation
```

### Direct Message Creation

```php
createUserMessage(string $content, array $metadata = []): Message
createAssistantMessage(string $content, array $metadata = []): Message
createSystemMessage(string $content, array $metadata = []): Message
createToolCallMessage(string $content, array $metadata = []): Message
createToolResultMessage(string $content, array $metadata = []): Message
```

### Relationships

```php
messages(): HasMany
messageChunks(): HasManyThrough
conversationable(): MorphTo
``` 