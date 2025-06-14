# Advanced Usage

This guide covers advanced features and patterns for working with Converse.

## Retrieving Messages

Converse provides several ways to retrieve and filter messages:

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

## Message Chunks for Streaming

When working with streamed messages, you can access the individual chunks:

```php
// Access chunks for a streamed message
$chunks = $message->chunks;

foreach ($chunks as $chunk) {
    echo $chunk->content;
    echo "Received at: " . $chunk->created_at;
}

// Get chunks in order
$orderedChunks = $message->chunks()->orderBy('sequence')->get();

// Reconstruct full message from chunks
$fullContent = $message->chunks()
    ->orderBy('sequence')
    ->pluck('content')
    ->implode('');
```

## Working with Metadata

The metadata JSON fields on conversations and messages enable powerful querying and filtering:

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

// Complex metadata queries
$specificChats = $user->conversations()
    ->where('metadata->model', 'gpt-4')
    ->where('metadata->temperature', '>', 0.7)
    ->whereJsonContains('metadata->tags', 'technical')
    ->get();
```

## Custom Scopes

Create custom scopes for common queries:

```php
// In your Conversation model
public function scopeRecent($query, $days = 7)
{
    return $query->where('created_at', '>=', now()->subDays($days));
}

public function scopeByProvider($query, $provider)
{
    return $query->where('metadata->provider', $provider);
}

public function scopeWithHighSatisfaction($query, $threshold = 4)
{
    return $query->where('metadata->satisfaction_rating', '>=', $threshold);
}

// Usage
$recentHighValueChats = $user->conversations()
    ->recent(30)
    ->byProvider('anthropic')
    ->withHighSatisfaction()
    ->get();
```

## Message Transformations

Transform messages for different AI providers:

```php
class MessageTransformer
{
    public function forOpenAI(Collection $messages): array
    {
        return $messages->map(function ($message) {
            return [
                'role' => $this->mapRole($message->role, 'openai'),
                'content' => $message->content,
                'name' => $message->metadata['name'] ?? null,
            ];
        })->filter()->toArray();
    }
    
    public function forAnthropic(Collection $messages): array
    {
        return $messages->map(function ($message) {
            if ($message->role->value === 'system') {
                return null; // Anthropic handles system messages differently
            }
            
            return [
                'role' => $this->mapRole($message->role, 'anthropic'),
                'content' => $message->content,
            ];
        })->filter()->values()->toArray();
    }
    
    private function mapRole($role, $provider): string
    {
        $mapping = [
            'openai' => [
                'user' => 'user',
                'assistant' => 'assistant',
                'system' => 'system',
                'tool_call' => 'assistant',
                'tool_result' => 'tool',
            ],
            'anthropic' => [
                'user' => 'user',
                'assistant' => 'assistant',
                'tool_call' => 'assistant',
                'tool_result' => 'user',
            ],
        ];
        
        return $mapping[$provider][$role->value] ?? $role->value;
    }
}
```

## Context Window Management

Manage token limits by selecting recent messages:

```php
class ContextWindowManager
{
    private $tokenLimits = [
        'gpt-3.5-turbo' => 4096,
        'gpt-4' => 8192,
        'gpt-4-32k' => 32768,
        'claude-3-5-sonnet' => 200000,
    ];
    
    public function selectMessages(
        Conversation $conversation, 
        string $model, 
        int $reserveTokens = 1000
    ): Collection {
        $limit = $this->tokenLimits[$model] ?? 4096;
        $availableTokens = $limit - $reserveTokens;
        
        $messages = collect();
        $tokenCount = 0;
        
        // Always include system messages
        $systemMessages = $conversation->messages()
            ->system()
            ->get();
            
        foreach ($systemMessages as $message) {
            $tokens = $this->estimateTokens($message->content);
            if ($tokenCount + $tokens <= $availableTokens) {
                $messages->push($message);
                $tokenCount += $tokens;
            }
        }
        
        // Add recent messages until we hit the limit
        $recentMessages = $conversation->messages()
            ->whereNotIn('role', ['system'])
            ->latest()
            ->get()
            ->reverse();
            
        foreach ($recentMessages as $message) {
            $tokens = $this->estimateTokens($message->content);
            if ($tokenCount + $tokens <= $availableTokens) {
                $messages->push($message);
                $tokenCount += $tokens;
            } else {
                break;
            }
        }
        
        return $messages->sortBy('created_at');
    }
    
    private function estimateTokens(string $text): int
    {
        // Rough estimation: ~4 characters per token
        return (int) ceil(strlen($text) / 4);
    }
}
```

## Conversation Templates

Create reusable conversation templates:

```php
class ConversationTemplates
{
    public function codeReview(User $user, array $context = []): Conversation
    {
        $conversation = $user->startConversation([
            'title' => 'Code Review - ' . ($context['file'] ?? 'Unknown'),
            'metadata' => [
                'template' => 'code_review',
                'context' => $context,
            ],
        ]);
        
        return $conversation->addMessages([
            new SystemMessage(view('prompts.code-reviewer', [
                'language' => $context['language'] ?? 'php',
                'standards' => $context['standards'] ?? ['psr-12'],
            ])),
            new UserMessage("Please review this code:\n\n" . $context['code']),
        ]);
    }
    
    public function customerSupport(User $user, array $context = []): Conversation
    {
        $conversation = $user->startConversation([
            'title' => 'Support - ' . ($context['issue'] ?? 'General'),
            'metadata' => [
                'template' => 'customer_support',
                'priority' => $context['priority'] ?? 'normal',
            ],
        ]);
        
        return $conversation->addMessages([
            new SystemMessage(view('prompts.customer-support', [
                'userName' => $user->name,
                'accountType' => $user->account_type,
                'history' => $user->support_history,
            ])),
            new UserMessage($context['message']),
        ]);
    }
}
```

## Performance Optimization

### Eager Loading

Always eager load relationships to avoid N+1 queries:

```php
// Bad - N+1 queries
$conversations = $user->conversations()->get();
foreach ($conversations as $conversation) {
    echo $conversation->messages->count(); // Queries each time
}

// Good - 2 queries total
$conversations = $user->conversations()->with('messages')->get();
foreach ($conversations as $conversation) {
    echo $conversation->messages->count(); // No additional queries
}

// Even better - aggregate in database
$conversations = $user->conversations()
    ->withCount('messages')
    ->get();
    
foreach ($conversations as $conversation) {
    echo $conversation->messages_count; // No additional queries
}
```

### Chunking Large Operations

Process large conversations in chunks:

```php
$conversation->messages()
    ->chunk(100, function ($messages) {
        foreach ($messages as $message) {
            // Process message
        }
    });
```

### Caching Expensive Operations

Cache computed values:

```php
class ConversationStatsService
{
    public function getStats(Conversation $conversation): array
    {
        return Cache::remember(
            "conversation.{$conversation->id}.stats",
            now()->addHour(),
            function () use ($conversation) {
                return [
                    'message_count' => $conversation->messages()->count(),
                    'total_tokens' => $conversation->messages()
                        ->sum('metadata->tokens'),
                    'average_response_time' => $this->calculateAverageResponseTime($conversation),
                    'topics' => $this->extractTopics($conversation),
                ];
            }
        );
    }
}
```

## Security Considerations

### Access Control

Always verify ownership before allowing access:

```php
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        // Check if user owns the conversation
        if ($conversation->conversationable_type === User::class &&
            $conversation->conversationable_id === $user->id) {
            return true;
        }
        
        // Check if user has been granted access
        return $conversation->sharedWith()
            ->where('user_id', $user->id)
            ->exists();
    }
}
```

### Input Sanitization

Sanitize user input before storing:

```php
class MessageSanitizer
{
    public function sanitize(string $content): string
    {
        // Remove any potential script tags
        $content = strip_tags($content, '<b><i><code><pre>');
        
        // Escape special characters
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        
        // Limit length
        return Str::limit($content, 10000);
    }
}
```

## Next Steps

- Explore [API Reference](/api/conversations) for all available methods
- Learn about [Events](/guide/events) for real-time features
- See [Examples](/examples/basic-chat) of advanced patterns in action 