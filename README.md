# Laravel Converse

[![Tests](https://github.com/elliottlawson/converse/actions/workflows/tests.yml/badge.svg)](https://github.com/elliottlawson/converse/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/elliottlawson/converse/v/stable)](https://packagist.org/packages/elliottlawson/converse)
[![License](https://poser.pugx.org/elliottlawson/converse/license)](https://packagist.org/packages/elliottlawson/converse)

A Laravel package for storing and managing AI conversation history with any LLM provider. Built to handle the real-world needs of AI-powered applications, including streaming responses, function calling, and conversation branching.

## Features

- **Provider Agnostic**: Works with any AI provider (OpenAI, Anthropic, Google, etc.)
- **Type-Safe Message Helpers**: Dedicated methods for each message type
- **Streaming Support**: Elegant handling of streaming responses with chunk storage
- **Event Driven**: Real-time broadcasting support for live updates
- **Soft Deletes**: Full soft delete support with cascading deletes
- **Flexible Storage**: JSON metadata fields for provider-specific data

## Installation

```bash
composer require elliottlawson/converse
```

Publish the config and migrations:

```bash
php artisan vendor:publish --provider="ElliottLawson\Converse\ConverseServiceProvider"
php artisan migrate
```

## Basic Usage

### Setting Up Your Model

```php
use App\Models\User;
use ElliottLawson\Converse\Traits\HasAIConversations;

class User extends Model
{
    use HasAIConversations;
}
```

### Creating Conversations

```php
$user = User::find(1);
$conversation = $user->startConversation([
    'title' => 'Help with Laravel',
    'metadata' => [
        'provider' => 'anthropic',
        'model' => 'claude-3-5-sonnet',
    ],
]);
```

### Adding Messages

```php
use ElliottLawson\Converse\Models\Conversation;

// Individual messages
$conversation->addUserMessage('How do I deploy Laravel to production?');
$conversation->addAssistantMessage('There are several ways to deploy Laravel...');
$conversation->addSystemMessage('You are a helpful Laravel expert.');

// Tool/Function calls
$conversation->addToolCallMessage('get_deployment_options(framework="laravel")');
$conversation->addToolResultMessage('{"options": ["Forge", "Vapor", "Docker"]}');

// Fluent chaining (perfect for building conversation context)
$conversation
    ->addSystemMessage('You are a Laravel expert with 10+ years experience')
    ->addUserMessage('Context: I have a Laravel app that needs deployment')
    ->addUserMessage('Requirements: High availability, auto-scaling, zero downtime')
    ->addUserMessage('Question: What deployment strategy do you recommend?');
```

### Two APIs: Choose What You Need

**Fluent API** - Returns `Conversation` for chaining:
```php
$conversation = $conversation
    ->addSystemMessage('You are helpful')
    ->addUserMessage('Hello')
    ->addAssistantMessage('Hi there!');
```

**Direct API** - Returns `Message` objects when you need immediate access:
```php
$message = $conversation->createUserMessage('Hello');
$messageId = $message->id;
$content = $message->content;
```

### Helper Methods

```php
// Get the most recently added message
$lastMessage = $conversation->getLastMessage();

// Get recent messages as a collection
$recentMessages = $conversation->getRecentMessages(5);

// Create a conversation subset with recent messages (useful for context windows)
$subset = $conversation->selectRecentMessages(10);
```

### Bulk Importing Messages

Perfect for importing conversation history or migrating from other systems:

```php
use ElliottLawson\Converse\Messages\UserMessage;
use ElliottLawson\Converse\Messages\AssistantMessage;
use ElliottLawson\Converse\Messages\SystemMessage;
use ElliottLawson\Converse\Enums\MessageRole;

// Using message DTOs
$messages = $conversation->addMessages([
    new SystemMessage('You are a Laravel expert'),
    new UserMessage('How do I deploy to production?'),
    new AssistantMessage('Let me help you with deployment...'),
]);

// With metadata in DTOs
$messages = $conversation->addMessages([
    new UserMessage('Analyze this code', ['timestamp' => now(), 'ip' => request()->ip()]),
    new AssistantMessage('I found several issues...', ['model' => 'gpt-4', 'tokens' => 245]),
]);

// Simple strings - pass multiple arguments directly (all treated as user messages)
$messages = $conversation->addMessages(
    'Hello',
    'I need help with Laravel',
    'How do I set up queues?'
);

// Or pass an array (all treated as user messages)
$messages = $conversation->addMessages([
    'Hello',
    'I need help with Laravel',
    'How do I set up queues?',
]);

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
```

### Working with Metadata

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

## Streaming Responses

Most AI providers stream responses token by token. This package makes it easy to store these streams while maintaining a great user experience:

```php
use ElliottLawson\Converse\Models\Message;

// Start streaming an assistant response
$message = $conversation->startStreamingAssistant([
    'model' => 'claude-3-5-sonnet',
    'request_id' => Str::uuid(),
]);

// In your streaming handler, append chunks as they arrive
foreach ($aiProvider->stream($prompt) as $chunk) {
    $message->appendChunk($chunk);
    
    // Broadcast to your frontend
    broadcast(new StreamUpdate($message, $chunk));
}

// When streaming completes successfully
$message->completeStreaming([
    'prompt_tokens' => 150,
    'completion_tokens' => 423,
    'total_tokens' => 573,
    'finish_reason' => 'stop',
    'duration_ms' => 2341,
]);

// Handle streaming failures gracefully
if ($error) {
    $message->failStreaming('Connection lost', [
        'error_code' => 'NETWORK_ERROR',
        'attempted_retry' => true,
        'partial_response' => true,
    ]);
}
```

You can also stream user input for voice transcription or real-time collaboration:

```php
$message = $conversation->startStreamingUser([
    'input_method' => 'voice',
    'language' => 'en-US',
]);
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

## Standalone Conversations

You can also create conversations without associating them with a model:

```php
use ElliottLawson\Converse\Models\Conversation;

$conversation = Conversation::create([
    'title' => 'Quick Chat',
    'metadata' => ['source' => 'api'],
]);

$conversation->addUserMessage('Hello!');
$conversation->addAssistantMessage('Hi there!');
```

## Events

The package dispatches events throughout the conversation lifecycle, perfect for real-time updates, analytics, and integrations:

```php
use ElliottLawson\Converse\Events\ConversationCreated;
use ElliottLawson\Converse\Events\MessageCreated;
use ElliottLawson\Converse\Events\ChunkReceived;
use ElliottLawson\Converse\Events\MessageCompleted;

// In your EventServiceProvider
protected $listen = [
    ConversationCreated::class => [
        SendWelcomeMessage::class,
        TrackConversationAnalytics::class,
    ],
    MessageCreated::class => [
        BroadcastMessageToUser::class,
        CheckForModeration::class,
    ],
    ChunkReceived::class => [
        StreamChunkToWebSocket::class,
    ],
    MessageCompleted::class => [
        CalculateCosts::class,
        UpdateUserCredits::class,
    ],
];
```

### Example Listeners

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\MessageCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class BroadcastMessageToUser implements ShouldQueue
{
    public function handle(MessageCreated $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;
        
        // Broadcast to user's private channel
        broadcast(new NewMessage($message))
            ->toOthers()
            ->onChannel("user.{$conversation->conversable_id}");
    }
}
```

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\ChunkReceived;

class StreamChunkToWebSocket
{
    public function handle(ChunkReceived $event): void
    {
        $chunk = $event->chunk;
        $message = $chunk->message;
        
        // Stream to conversation channel
        broadcast(new StreamUpdate(
            conversationId: $message->conversation_id,
            messageId: $message->id,
            chunk: $chunk->content,
            sequence: $chunk->sequence
        ))->toOthers();
    }
}
```

```php
namespace App\Listeners;

use ElliottLawson\Converse\Events\MessageCompleted;

class CalculateCosts
{
    public function handle(MessageCompleted $event): void
    {
        $message = $event->message;
        
        if ($message->role->value === 'assistant' && isset($message->metadata['tokens'])) {
            $cost = $this->calculateTokenCost(
                $message->metadata['prompt_tokens'] ?? 0,
                $message->metadata['completion_tokens'] ?? 0,
                $message->metadata['model'] ?? 'gpt-3.5-turbo'
            );
            
            // Store cost for billing
            $message->conversation->conversable->billing()->create([
                'tokens_used' => $message->metadata['tokens'],
                'cost' => $cost,
                'model' => $message->metadata['model'],
            ]);
        }
    }
}

## Advanced Usage

### Retrieving Messages

```php
// Get all messages in a conversation
$messages = $conversation->messages;

// Get the last message
$lastMessage = $conversation->lastMessage;

// Use scopes for cleaner queries
$userMessages = $conversation->messages()->user()->get();
$assistantMessages = $conversation->messages()->assistant()->get();
$systemMessages = $conversation->messages()->system()->get();

// Get only completed messages
$completed = $conversation->messages()->completed()->get();

// Get messages still streaming
$streaming = $conversation->messages()->streaming()->first();

// Get failed messages
$failed = $conversation->messages()->failed()->get();

// Combine scopes
$failedUserMessages = $conversation->messages()
    ->user()
    ->failed()
    ->get();
```

### Message Chunks for Streaming

```php
// Access chunks for a streamed message
$chunks = $message->chunks;

foreach ($chunks as $chunk) {
    echo $chunk->content;
    echo "Received at: " . $chunk->created_at;
}
```

### Working with Metadata

```php
// Query conversations by metadata
$openAIChats = $user->conversations()
    ->where('metadata->provider', 'openai')
    ->get();

// Find high-token conversations
$expensive = $user->conversations()
    ->whereHas('messages', function ($query) {
        $query->where('metadata->tokens', '>', 1000);
    })
    ->get();
```

### Finding Conversations by UUID

UUIDs are useful for public URLs, APIs, and external references:

```php
// For public links or API endpoints
$conversation = Conversation::where('uuid', $uuid)->firstOrFail();

// Verify ownership if needed
if ($conversation->conversable_id !== $user->id) {
    abort(403);
}
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.