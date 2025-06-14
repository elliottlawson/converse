# Traits API Reference

## HasAIConversations

The `HasAIConversations` trait enables any Eloquent model to manage AI conversations.

### Installation

Add the trait to any model that should have conversations:

```php
use ElliottLawson\Converse\Traits\HasAIConversations;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasAIConversations;
}
```

### Available Methods

#### `conversations(): MorphMany`

Get all conversations for the model.

```php
$conversations = $user->conversations;

// With additional queries
$recentConversations = $user->conversations()
    ->where('created_at', '>', now()->subDays(7))
    ->latest()
    ->get();
```

#### `startConversation(array $attributes = []): Conversation`

Start a new conversation.

```php
// Basic usage
$conversation = $user->startConversation();

// With attributes
$conversation = $user->startConversation([
    'title' => 'Customer Support Chat',
    'metadata' => [
        'source' => 'web',
        'category' => 'billing',
    ],
]);
```

#### `continueConversation(string $conversationId): Conversation`

Continue an existing conversation by ID.

```php
try {
    $conversation = $user->continueConversation($conversationId);
    $conversation->addUserMessage('I have another question...');
} catch (ModelNotFoundException $e) {
    // Conversation not found or doesn't belong to user
}
```

#### `activeConversations(): MorphMany`

Get only active (non-deleted) conversations.

```php
$activeConversations = $user->activeConversations;

// Count active conversations
$activeCount = $user->activeConversations()->count();
```

### Usage Examples

#### Multiple Model Types

The trait can be used on any Eloquent model:

```php
// On User model
class User extends Model
{
    use HasAIConversations;
}

// On Team model
class Team extends Model
{
    use HasAIConversations;
}

// On Project model
class Project extends Model
{
    use HasAIConversations;
}

// Usage
$userConversation = $user->startConversation(['title' => 'Personal Assistant']);
$teamConversation = $team->startConversation(['title' => 'Team Planning']);
$projectConversation = $project->startConversation(['title' => 'Code Review']);
```

#### Managing Conversations

```php
// Get conversation statistics
$stats = [
    'total' => $user->conversations()->count(),
    'active' => $user->activeConversations()->count(),
    'today' => $user->conversations()
        ->whereDate('created_at', today())
        ->count(),
];

// Get conversations by metadata
$billingChats = $user->conversations()
    ->whereJsonContains('metadata->category', 'billing')
    ->get();

// Soft delete old conversations
$user->conversations()
    ->where('updated_at', '<', now()->subMonths(6))
    ->delete();
```

#### Working with Different Models

```php
// Find which model owns a conversation
$conversation = Conversation::find($id);
$owner = $conversation->conversable; // Returns User, Team, or Project instance

// Get owner type
$ownerType = $conversation->conversable_type; // e.g., "App\Models\User"
$ownerId = $conversation->conversable_id;
```

### Database Schema

The trait expects the following columns in your conversations table:

- `conversable_type` - The model class name
- `conversable_id` - The model ID

These are automatically handled by the package migrations. 