# Conversations

Conversations are the primary container for organizing message history in Converse. Each conversation represents a distinct chat session with its own messages, metadata, and lifecycle.

## Understanding Conversations

A Conversation is a standard Eloquent model that can be queried and manipulated just like any other Laravel model. We've enhanced it with helpful methods and scopes specifically designed for managing AI chat sessions, but at its core, it's a regular model you already know how to work with.

## Creating Conversations

### Through a Model Relationship

The most common way to create conversations is through a model that uses the `HasAIConversations` trait:

```php
use App\Models\User;

$user = User::find(1);

// Create a new conversation
$conversation = $user->startConversation([
    'title' => 'Help with Laravel',
    'metadata' => [
        'provider' => 'anthropic',
        'model' => 'claude-3-5-sonnet',
        'purpose' => 'technical_support',
    ],
]);
```

### Standalone Conversations

You can also create conversations without associating them with a model:

```php
use ElliottLawson\Converse\Models\Conversation;

$conversation = Conversation::create([
    'title' => 'Quick Chat',
    'metadata' => ['source' => 'api', 'session_id' => 'abc123'],
]);

$conversation->addUserMessage('Hello!');
$conversation->addAssistantMessage('Hi there!');
```

## Managing Conversations

The package provides several methods to organize and retrieve conversations:

```php
use App\Models\User;
use ElliottLawson\Converse\Models\Conversation;

$user = User::find(1);

// Get all conversations for a user
$conversations = $user->conversations()
    ->latest()
    ->get();

// Find a conversation by its ID
$conversation = $user->conversations()->find($conversationId);

// Get only active conversations (excluding soft-deleted)
$activeChats = $user->activeConversations()
    ->whereDate('created_at', '>=', now()->subDays(7))
    ->get();

// Continue an existing conversation
$conversation = $user->continueConversation($conversationId);
$conversation->addUserMessage('Actually, I have another question...');

// Clean up old conversations
$user->conversations()
    ->where('updated_at', '<', now()->subMonths(6))
    ->each->delete(); // Soft deletes

// Restore an accidentally deleted conversation
$conversation = $user->conversations()
    ->withTrashed()
    ->find($id);
    
$conversation->restore();
```

## Conversation Properties

Each conversation has the following properties:

- `id` - Unique identifier
- `uuid` - UUID for public references
- `conversationable_type` - Polymorphic relation type (nullable for standalone)
- `conversationable_id` - Polymorphic relation ID (nullable for standalone)
- `title` - Conversation title
- `metadata` - JSON field for storing additional data
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp
- `deleted_at` - Soft delete timestamp

## Working with Metadata

The metadata field allows flexible storage of conversation-specific information:

```php
// Store provider information
$conversation = $user->startConversation([
    'title' => 'Code Review',
    'metadata' => [
        'provider' => 'openai',
        'model' => 'gpt-4',
        'temperature' => 0.7,
        'max_tokens' => 2000,
        'purpose' => 'code_review',
        'language' => 'php',
    ],
]);

// Update metadata
$conversation->update([
    'metadata' => array_merge($conversation->metadata ?? [], [
        'last_topic' => 'authentication',
        'satisfaction_rating' => 5,
    ])
]);

// Query by metadata
$codeReviews = $user->conversations()
    ->where('metadata->purpose', 'code_review')
    ->get();

$highValueConversations = $user->conversations()
    ->where('metadata->satisfaction_rating', '>=', 4)
    ->get();
```

## Built-in Scopes

Use built-in scopes for common queries:

```php
// Recent conversations
$recent = $user->conversations()->recent()->get();

// Conversations with messages
$active = $user->conversations()->hasMessages()->get();

// Conversations by date range
$thisWeek = $user->conversations()
    ->whereDate('created_at', '>=', now()->startOfWeek())
    ->get();
```

## Relationships

Conversations have several important relationships:

```php
// Get all messages
$messages = $conversation->messages;

// Get the last message
$lastMessage = $conversation->messages()->latest()->first();

// Get message chunks (for streaming)
$chunks = $conversation->messageChunks;

// Get the owner (if not standalone)
$owner = $conversation->conversationable;
```

## Soft Deletes

Conversations support soft deletes with cascading:

```php
// Soft delete a conversation
$conversation->delete();

// Include soft-deleted conversations in queries
$allConversations = $user->conversations()->withTrashed()->get();

// Get only soft-deleted conversations
$deleted = $user->conversations()->onlyTrashed()->get();

// Restore a soft-deleted conversation
$conversation->restore();

// Permanently delete
$conversation->forceDelete();
```



## Next Steps

- Learn about [Messages](/guide/messages) and how to add them
- Explore [Bulk Operations](/guide/bulk-operations) for importing conversations
- Understand [Soft Deletes](/guide/soft-deletes) and data retention 