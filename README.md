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

### Conditional Fluent Chaining

The Conversation class uses Laravel's `Conditionable` trait, enabling powerful conditional logic in your fluent chains with `when()` and `unless()` methods. This allows you to build dynamic conversations that adapt to different contexts, user preferences, and application states.

#### Basic Usage

```php
$conversation = $conversation
    ->addSystemMessage('You are a helpful assistant')
    ->when($user->prefers_formal_tone, function ($conversation) {
        $conversation->addSystemMessage('Please maintain a professional and formal tone in all responses.');
    })
    ->when($user->language !== 'en', function ($conversation) use ($user) {
        $conversation->addSystemMessage("Please respond in {$user->language}.");
    })
    ->addUserMessage($userInput);
```

#### Conditional Message Addition Based on User Preferences

```php
// Build conversation context based on user settings
$conversation = $conversation
    ->when($user->expert_mode, function ($conversation) {
        $conversation->addSystemMessage('Provide detailed technical explanations with code examples.');
    }, function ($conversation) {
        $conversation->addSystemMessage('Explain concepts in simple terms, avoiding technical jargon.');
    })
    ->when($user->prefers_bullet_points, function ($conversation) {
        $conversation->addSystemMessage('Format responses using bullet points for clarity.');
    })
    ->unless($user->allows_external_links, function ($conversation) {
        $conversation->addSystemMessage('Do not include external links in responses.');
    });
```

#### Dynamic Context Building Based on Conversation State

```php
// Add context based on conversation history
$messageCount = $conversation->messages()->count();
$hasCodeInHistory = $conversation->messages()
    ->where('content', 'like', '%```%')
    ->exists();

$conversation = $conversation
    ->when($messageCount === 0, function ($conversation) {
        // First message - add introduction
        $conversation->addSystemMessage('Greet the user warmly as this is their first message.');
    })
    ->when($messageCount > 10, function ($conversation) {
        // Long conversation - add summary reminder
        $conversation->addSystemMessage('This is a long conversation. Stay focused on the current topic.');
    })
    ->when($hasCodeInHistory, function ($conversation) {
        // Code discussion detected
        $conversation->addSystemMessage('Continue using the same programming language and style as earlier in the conversation.');
    });
```

#### Conditional Tool/Function Availability

```php
// Enable tools based on user permissions and context
$conversation = $conversation
    ->addSystemMessage('You are an AI assistant with access to various tools.')
    ->when($user->can('access-database'), function ($conversation) {
        $conversation->addSystemMessage('You have access to database query tools. Use them when users ask about data.');
    })
    ->when($user->subscription === 'premium', function ($conversation) {
        $conversation->addSystemMessage('You have access to advanced analysis tools including code execution and web browsing.');
    })
    ->unless($user->is_restricted, function ($conversation) {
        $conversation->addSystemMessage('You can help with code generation and technical tasks.');
    });
```

#### Environment-Specific Behavior

```php
// Adapt conversation based on application environment
$conversation = $conversation
    ->when(app()->environment('production'), function ($conversation) {
        $conversation->addSystemMessage('Ensure all responses are production-safe. Do not expose sensitive information.');
    }, function ($conversation) {
        $conversation->addSystemMessage('Development mode: Include debugging information when relevant.');
    })
    ->when(config('app.demo_mode'), function ($conversation) {
        $conversation->addSystemMessage('This is a demo. Mention feature limitations where applicable.');
    });
```

#### Complex Conditional Logic

```php
// Build sophisticated conversation contexts with multiple conditions
$isBusinessHours = now()->between('09:00', '17:00');
$userTimezone = $user->timezone ?? 'UTC';
$previousTopics = $conversation->messages()
    ->where('role', 'user')
    ->pluck('metadata.topic')
    ->filter()
    ->unique();

$conversation = $conversation
    ->when($isBusinessHours && $user->prefers_quick_responses, function ($conversation) {
        $conversation->addSystemMessage('Provide concise responses as the user prefers quick answers during business hours.');
    })
    ->when($previousTopics->contains('technical'), function ($conversation) use ($previousTopics) {
        $conversation->addSystemMessage(
            'Previous topics discussed: ' . $previousTopics->implode(', ') . 
            '. Maintain consistency with earlier technical discussions.'
        );
    })
    ->unless($isBusinessHours, function ($conversation) use ($userTimezone) {
        $conversation->addSystemMessage(
            "Note: User's local time is outside business hours ({$userTimezone}). " .
            "Be mindful they may be working late or in a different context."
        );
    });
```

#### Chaining Multiple Conditions

The `when()` and `unless()` methods return the conversation instance, allowing unlimited chaining:

```php
$conversation = $conversation
    ->when($conditionA, fn($c) => $c->addSystemMessage('A is true'))
    ->when($conditionB, fn($c) => $c->addSystemMessage('B is true'))
    ->unless($conditionC, fn($c) => $c->addSystemMessage('C is false'))
    ->when($conditionD && $conditionE, fn($c) => $c->addSystemMessage('D and E are true'))
    ->addUserMessage($userInput);
```

This feature enables you to build highly dynamic and context-aware conversations that adapt to user preferences, application state, and conversation history, all while maintaining clean and readable code.

### Inline Conditional Helpers

For simple conditional message additions, the conversation class provides inline conditional helper methods that offer a more concise syntax compared to `when()` and `unless()` closures. These methods are perfect when you need to conditionally add a single message without complex logic.

#### Fluent API Methods

Use these methods when building conversation chains:

```php
// Add a message only if condition is true
$conversation = $conversation
    ->addMessageIf($user->needs_context, 'system', 'You have access to the user\'s previous purchase history.')
    ->addMessageIf($request->has('tone'), 'system', 'Please respond in a ' . $request->tone . ' tone.')
    ->addUserMessage($userInput);

// Add a message only if condition is false
$conversation = $conversation
    ->addMessageUnless($user->is_anonymous, 'system', 'The user\'s name is ' . $user->name)
    ->addMessageUnless($conversation->messages()->count() > 0, 'system', 'This is the start of a new conversation.')
    ->addUserMessage($userInput);
```

#### Direct API Methods

Use these methods when you need immediate access to the created message:

```php
// Create and return a message if condition is true
$message = $conversation->createMessageIf(
    $user->wants_code_examples,
    'system',
    'Include code examples in your responses.'
);

if ($message) {
    Log::info('Added code examples instruction', ['message_id' => $message->id]);
}

// Create and return a message if condition is false
$welcomeMessage = $conversation->createMessageUnless(
    $user->returning_visitor,
    'assistant',
    'Welcome! I see this is your first time here. How can I help you today?'
);
```

#### Comparison with when/unless

Here's how inline conditional helpers compare to traditional `when()` and `unless()` methods:

```php
// Traditional approach with when()
$conversation = $conversation
    ->when($user->prefers_examples, function ($conversation) {
        $conversation->addSystemMessage('Include examples in your explanations.');
    })
    ->when($user->language !== 'en', function ($conversation) use ($user) {
        $conversation->addSystemMessage('Respond in ' . $user->language);
    });

// Cleaner approach with inline helpers
$conversation = $conversation
    ->addMessageIf($user->prefers_examples, 'system', 'Include examples in your explanations.')
    ->addMessageIf($user->language !== 'en', 'system', 'Respond in ' . $user->language);
```

#### With Metadata

Both conditional helper methods support metadata:

```php
// Fluent API with metadata
$conversation = $conversation
    ->addMessageIf(
        $user->track_preferences,
        'system',
        'User prefers detailed explanations',
        ['preference_type' => 'detail_level', 'value' => 'high']
    );

// Direct API with metadata
$debugMessage = $conversation->createMessageIf(
    app()->environment('development'),
    'system',
    'Debug mode is active',
    ['environment' => app()->environment(), 'debug' => config('app.debug')]
);
```

#### Practical Examples

```php
// Building context based on user settings
$conversation = $conversation
    ->addMessageIf($user->has_premium, 'system', 'User has premium access to advanced features.')
    ->addMessageUnless($user->allows_data_usage, 'system', 'Do not use external data sources.')
    ->addMessageIf($user->expertise === 'beginner', 'system', 'Explain concepts simply.')
    ->addMessageIf($user->expertise === 'expert', 'system', 'You can use technical terminology.')
    ->addUserMessage($userInput);

// Conditional instructions based on request context
$conversation = $conversation
    ->addMessageIf($request->has('format'), 'system', 'Format the response as ' . $request->format)
    ->addMessageIf($request->boolean('include_sources'), 'system', 'Cite sources for any factual claims.')
    ->addMessageUnless($request->boolean('allow_humor'), 'system', 'Maintain a strictly professional tone.')
    ->addUserMessage($request->input('message'));

// Setting up conversation based on feature flags
$conversation = $conversation
    ->addMessageIf(Feature::active('ai-web-search'), 'system', 'You can search the web for current information.')
    ->addMessageIf(Feature::active('ai-code-execution'), 'system', 'You can execute code to solve problems.')
    ->addMessageUnless(Feature::active('ai-image-generation'), 'system', 'You cannot generate images.')
    ->addUserMessage($userInput);
```

These inline conditional helpers make your code more readable and concise when dealing with simple conditional logic, while `when()` and `unless()` remain available for more complex scenarios that require multiple operations or access to the conversation instance.

### View Support

Laravel Converse seamlessly integrates with Laravel's view system, allowing you to pass Blade views directly to message methods without needing to call `->render()`. This feature makes managing complex prompts much easier by keeping them organized in dedicated Blade files rather than cluttering your PHP code with long strings.

#### Basic Usage with Views

Instead of writing long prompts inline, you can now pass views directly:

```php
// Traditional approach with inline strings
$conversation->addSystemMessage('You are an AI assistant specialized in Laravel development...');

// New approach with views
$conversation->addSystemMessage(view('prompts.laravel-expert'));
```

The view file (`resources/views/prompts/laravel-expert.blade.php`):
```blade
You are an AI assistant specialized in Laravel development with deep knowledge of:
- Laravel's architecture and design patterns
- Best practices for application structure
- Performance optimization techniques
- Security considerations
- Testing strategies

Always provide code examples when relevant and explain the reasoning behind your recommendations.
```

#### Passing Data to Views

You can pass data to views just like in any Laravel application:

```php
// Pass user preferences and context to the view
$conversation->addSystemMessage(
    view('prompts.personalized-assistant', [
        'userName' => $user->name,
        'expertise' => $user->expertise_level,
        'preferences' => $user->ai_preferences,
        'language' => $user->preferred_language,
    ])
);
```

The view file (`resources/views/prompts/personalized-assistant.blade.php`):
```blade
You are a helpful assistant for {{ $userName }}.

@if($expertise === 'beginner')
    Explain concepts in simple terms and avoid technical jargon.
@elseif($expertise === 'intermediate')
    Provide balanced explanations with some technical details.
@else
    Feel free to use advanced technical terminology and dive deep into implementations.
@endif

@if($preferences->get('examples'))
    Always include practical examples in your responses.
@endif

@if($language !== 'en')
    Please respond in {{ $language }}.
@endif
```

#### Using Views for Complex Prompts

Views are particularly powerful for complex, multi-part prompts that would be unwieldy as strings:

```php
// Building a code review prompt with extensive guidelines
$conversation->addSystemMessage(
    view('prompts.code-reviewer', [
        'standards' => $project->coding_standards,
        'focusAreas' => ['security', 'performance', 'readability'],
        'severity' => 'strict',
    ])
);

// Adding user's code for review
$conversation->addUserMessage(
    view('prompts.review-request', [
        'code' => $codeToReview,
        'context' => $pullRequest->description,
        'files' => $pullRequest->changed_files,
    ])
);
```

The code reviewer prompt (`resources/views/prompts/code-reviewer.blade.php`):
```blade
You are an expert code reviewer. Please analyze code according to these guidelines:

## Coding Standards
@foreach($standards as $standard)
- {{ $standard->name }}: {{ $standard->description }}
@endforeach

## Focus Areas
@foreach($focusAreas as $area)
    @switch($area)
        @case('security')
            ### Security Review
            - Check for SQL injection vulnerabilities
            - Validate all user inputs
            - Ensure proper authentication and authorization
            - Look for exposed sensitive data
            @break
            
        @case('performance')
            ### Performance Review
            - Identify N+1 query problems
            - Check for inefficient loops
            - Review database indexing opportunities
            - Suggest caching strategies where appropriate
            @break
            
        @case('readability')
            ### Readability Review
            - Ensure clear variable and function names
            - Check for proper code organization
            - Verify adequate comments for complex logic
            - Suggest refactoring for overly complex methods
            @break
    @endswitch
@endforeach

Severity Level: {{ ucfirst($severity) }}
@if($severity === 'strict')
    Flag even minor issues and suggest improvements for all suboptimal patterns.
@endif
```

#### Mixing Views and Strings

You can freely mix views with regular strings in your conversation flow:

```php
$conversation
    ->addSystemMessage(view('prompts.base-assistant'))
    ->addSystemMessage('Additionally, today is ' . now()->format('l, F j, Y'))
    ->addUserMessage(view('templates.question', ['topic' => $request->topic]))
    ->when($includeContext, function ($conversation) use ($context) {
        $conversation->addSystemMessage(view('prompts.context', compact('context')));
    })
    ->addUserMessage($request->question);
```

#### Using Views with Conditional Helpers

Views work seamlessly with the conditional helper methods:

```php
$conversation
    ->addMessageIf(
        $user->wants_examples,
        'system',
        view('prompts.include-examples')
    )
    ->addMessageUnless(
        $user->is_premium,
        'system',
        view('prompts.limitations.free-tier')
    )
    ->when($user->has_custom_instructions, function ($conversation) use ($user) {
        $conversation->addSystemMessage(
            view('prompts.custom-instructions', [
                'instructions' => $user->custom_instructions
            ])
        );
    });
```

#### Benefits of Using Views for Prompts

1. **Organization**: Keep prompts organized in a dedicated directory structure
2. **Reusability**: Share common prompts across different parts of your application
3. **Maintainability**: Update prompts without touching controller logic
4. **Version Control**: Track prompt changes separately from code changes
5. **Blade Features**: Leverage all Blade features like includes, components, and layouts
6. **Syntax Highlighting**: Get proper syntax highlighting in your IDE for complex prompts
7. **Testing**: Easily test prompt generation with different data inputs

Example directory structure:
```
resources/views/prompts/
├── agents/
│   ├── customer-support.blade.php
│   ├── technical-expert.blade.php
│   └── sales-assistant.blade.php
├── contexts/
│   ├── user-history.blade.php
│   ├── session-data.blade.php
│   └── business-rules.blade.php
├── templates/
│   ├── question.blade.php
│   ├── analysis-request.blade.php
│   └── summary-request.blade.php
└── shared/
    ├── base-instructions.blade.php
    └── safety-guidelines.blade.php
```

This view support feature transforms how you manage AI prompts in your Laravel application, making them as maintainable and organized as the rest of your codebase.

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