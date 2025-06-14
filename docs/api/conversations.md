# Conversations API Reference

Complete API documentation for the Conversation model and methods.

## Model Properties

| Property | Type | Description |
|----------|------|-------------|
| `id` | integer | Unique identifier |
| `conversable_type` | string | Polymorphic relation type |
| `conversable_id` | integer | Polymorphic relation ID |
| `title` | string | Conversation title |
| `metadata` | json | Additional metadata |
| `created_at` | timestamp | Creation timestamp |
| `updated_at` | timestamp | Last update timestamp |
| `deleted_at` | timestamp | Soft delete timestamp |

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