# Message Chunks API Reference

Documentation for the MessageChunk model used in streaming responses.

## Model Properties

- `id` - Unique identifier
- `message_id` - Parent message ID
- `index` - Chunk order index
- `content` - Chunk content
- `created_at` - Creation timestamp

## Usage

Message chunks are automatically created when handling streaming responses.

## Relationships

```php
message(): BelongsTo
```

## Example

```php
// Example coming soon
``` 