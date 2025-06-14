# Soft Deletes

Converse uses Laravel's standard soft delete functionality with automatic cascading behavior. All the standard Laravel soft delete features work as expected (`withTrashed()`, `onlyTrashed()`, `restore()`, `forceDelete()`, etc.).

## What Makes Converse Special

### Automatic Cascading

When you soft delete a conversation, all associated messages and chunks are automatically soft deleted:

```php
// Soft delete a conversation
$conversation->delete();
// All messages and chunks are now soft deleted too
```

### Automatic Restoration

When you restore a conversation, all associated messages and chunks are automatically restored:

```php
// Restore a soft-deleted conversation  
$conversation->restore();
// All messages and chunks are now restored too
```

### Force Delete Cascading

When force deleting a conversation, all related records are permanently removed:

```php
// Permanently deletes the conversation AND all messages and chunks
$conversation->forceDelete();
```

## Standard Laravel Features

All standard Laravel soft delete functionality works as expected:

```php
// Query including soft deleted records
$allConversations = $user->conversations()->withTrashed()->get();

// Get only soft deleted records
$deletedConversations = $user->conversations()->onlyTrashed()->get();

// Check if a record is soft deleted
if ($conversation->trashed()) {
    // Conversation is soft deleted
}

// Restore individual messages
$message = Message::onlyTrashed()->find($messageId);
$message->restore();
```

## Important Notes

- The cascading behavior happens automatically - you don't need to manually delete messages
- This cascading applies to both soft deletes and force deletes
- Messages can still be individually soft deleted or restored if needed
- No custom events are fired beyond Laravel's standard model events

## Next Steps

- Learn about [Events](/guide/events) triggered by soft deletes
- Explore [Advanced Usage](/guide/advanced) for more patterns
- See [Broadcasting](/guide/broadcasting) for real-time delete notifications 