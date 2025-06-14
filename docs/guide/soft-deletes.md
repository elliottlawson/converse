# Soft Deletes

Laravel Converse provides full support for soft deletes with automatic cascading, allowing you to safely delete conversations and messages while maintaining data integrity and the ability to restore them if needed.

## Basic Usage

Soft deleting a conversation will automatically soft delete all associated messages and chunks:

```php
// Soft delete a conversation
$conversation->delete();

// All messages and chunks are now soft deleted too
```

## Querying with Soft Deletes

### Including Soft Deleted Records

```php
// Include soft-deleted conversations in queries
$allConversations = $user->conversations()->withTrashed()->get();

// Get only soft-deleted conversations
$deletedConversations = $user->conversations()->onlyTrashed()->get();

// Check if a conversation is soft deleted
if ($conversation->trashed()) {
    // Conversation is soft deleted
}
```

### Working with Soft Deleted Messages

```php
// Include soft deleted messages
$allMessages = $conversation->messages()->withTrashed()->get();

// Get only soft deleted messages
$deletedMessages = $conversation->messages()->onlyTrashed()->get();

// Count including soft deleted
$totalMessages = $conversation->messages()->withTrashed()->count();
```

## Restoring Data

### Restoring Conversations

When you restore a conversation, all associated messages and chunks are automatically restored:

```php
// Restore a soft-deleted conversation
$conversation->restore();

// All messages and chunks are now restored too
```

### Selective Restoration

You can also restore specific messages:

```php
// Restore specific messages
$message = Message::onlyTrashed()->find($messageId);
$message->restore();

// Restore messages matching criteria
$conversation->messages()
    ->onlyTrashed()
    ->where('role', 'user')
    ->restore();
```

## Permanent Deletion

### Force Deleting

To permanently delete records from the database:

```php
// Permanently delete a conversation and all related data
$conversation->forceDelete();

// Force delete specific messages
$conversation->messages()
    ->where('created_at', '<', now()->subYear())
    ->forceDelete();
```

### Cascading Force Deletes

When force deleting a conversation, all related records are permanently removed:

```php
// This will permanently delete:
// - The conversation
// - All messages
// - All message chunks
$conversation->forceDelete();
```

## Implementing Retention Policies

### Automatic Cleanup

Create a scheduled job to clean up old conversations:

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use ElliottLawson\Converse\Models\Conversation;

class CleanupOldConversations extends Command
{
    protected $signature = 'conversations:cleanup {--days=90}';
    
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);
        
        // Soft delete old conversations
        $count = Conversation::where('updated_at', '<', $cutoffDate)
            ->delete();
            
        $this->info("Soft deleted {$count} conversations older than {$days} days.");
        
        // Permanently delete conversations soft deleted over 30 days ago
        $permanentCount = Conversation::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(30))
            ->forceDelete();
            
        $this->info("Permanently deleted {$permanentCount} conversations.");
    }
}
```

### User-Specific Retention

Implement different retention policies per user:

```php
class ConversationRetentionService
{
    public function applyRetentionPolicy(User $user): void
    {
        $policy = $this->getPolicyForUser($user);
        
        // Soft delete based on policy
        $user->conversations()
            ->where('updated_at', '<', now()->subDays($policy->soft_delete_after_days))
            ->each->delete();
            
        // Force delete based on policy
        $user->conversations()
            ->onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($policy->force_delete_after_days))
            ->each->forceDelete();
    }
    
    private function getPolicyForUser(User $user): object
    {
        return (object) match($user->subscription_tier) {
            'premium' => [
                'soft_delete_after_days' => 365,
                'force_delete_after_days' => 90,
            ],
            'standard' => [
                'soft_delete_after_days' => 90,
                'force_delete_after_days' => 30,
            ],
            default => [
                'soft_delete_after_days' => 30,
                'force_delete_after_days' => 7,
            ],
        };
    }
}
```

## Archiving Instead of Deleting

For compliance or audit purposes, you might want to archive instead of delete:

```php
class ConversationArchiver
{
    public function archive(Conversation $conversation): void
    {
        // Export conversation data
        $export = [
            'conversation' => $conversation->toArray(),
            'messages' => $conversation->messages->toArray(),
            'metadata' => [
                'archived_at' => now(),
                'archived_by' => auth()->id(),
            ],
        ];
        
        // Store in archive storage
        Storage::disk('archive')->put(
            "conversations/{$conversation->uuid}.json",
            json_encode($export)
        );
        
        // Mark as archived in metadata
        $conversation->update([
            'metadata' => array_merge($conversation->metadata ?? [], [
                'archived' => true,
                'archived_at' => now(),
            ])
        ]);
        
        // Then soft delete
        $conversation->delete();
    }
}
```

## Soft Delete Events

Listen for soft delete events:

```php
namespace App\Observers;

use ElliottLawson\Converse\Models\Conversation;

class ConversationObserver
{
    public function deleting(Conversation $conversation): void
    {
        // Before soft delete
        Log::info('Soft deleting conversation', [
            'id' => $conversation->id,
            'user_id' => $conversation->conversable_id,
        ]);
    }
    
    public function deleted(Conversation $conversation): void
    {
        // After soft delete
        event(new ConversationDeleted($conversation));
    }
    
    public function restoring(Conversation $conversation): void
    {
        // Before restore
        Log::info('Restoring conversation', ['id' => $conversation->id]);
    }
    
    public function restored(Conversation $conversation): void
    {
        // After restore
        event(new ConversationRestored($conversation));
    }
}
```

## Best Practices

1. **Regular Cleanup**: Schedule regular cleanup jobs to manage database size
2. **Clear Policies**: Document and communicate your data retention policies
3. **User Control**: Allow users to permanently delete their own data
4. **Audit Trail**: Log all deletions and restorations for compliance
5. **Backup Before Force Delete**: Consider backing up data before permanent deletion

```php
// Good practice: User-initiated deletion with confirmation
public function deleteConversation(Request $request, Conversation $conversation)
{
    $this->authorize('delete', $conversation);
    
    if ($request->boolean('permanent') && $request->boolean('confirmed')) {
        // Log the permanent deletion
        Log::info('User permanently deleted conversation', [
            'user_id' => auth()->id(),
            'conversation_id' => $conversation->id,
            'ip' => $request->ip(),
        ]);
        
        $conversation->forceDelete();
        
        return response()->json(['message' => 'Permanently deleted']);
    }
    
    $conversation->delete();
    
    return response()->json(['message' => 'Conversation deleted. Can be restored within 30 days.']);
}
```

## Next Steps

- Learn about [Events](/guide/events) triggered by soft deletes
- Explore [Advanced Usage](/guide/advanced) for more patterns
- See [Broadcasting](/guide/broadcasting) for real-time delete notifications 