# Using Converse with Prism

[Prism](https://github.com/elliottlawson/converse-prism) is a powerful Laravel package that provides a fluent interface for interacting with various AI providers. When combined with Converse, you get the best of both worlds: Prism's elegant AI integration and Converse's robust conversation management.

## Installation

First, install both packages:

```bash
composer require elliottlawson/converse
composer require echolabsdev/prism
```

## Basic Integration

Here's how to use Prism with Converse to create AI-powered conversations:

```php
use EchoLabs\Prism\Facades\Prism;
use ElliottLawson\Converse\Messages\UserMessage;
use ElliottLawson\Converse\Messages\AssistantMessage;

// Start a conversation
$conversation = $user->startConversation([
    'title' => 'AI Assistant Chat',
    'metadata' => ['provider' => 'anthropic']
]);

// Add user message
$conversation->addUserMessage('Can you help me write a Laravel migration?');

// Get AI response using Prism
$response = Prism::text()
    ->using('claude-3.5-sonnet')
    ->withMessages($conversation->messages->map(fn($msg) => [
        'role' => $msg->role->value,
        'content' => $msg->content
    ])->toArray())
    ->generate();

// Store the response
$conversation->addAssistantMessage($response->text);
```

## Streaming Responses

One of the most powerful features is streaming AI responses directly into Converse:

```php
use EchoLabs\Prism\Facades\Prism;

// Start a streaming message
$message = $conversation->startStreamingAssistant();

// Stream response from Prism
Prism::text()
    ->using('claude-3.5-sonnet')
    ->withMessages($conversation->messages->map(fn($msg) => [
        'role' => $msg->role->value,
        'content' => $msg->content
    ])->toArray())
    ->stream(function (string $chunk) use ($message) {
        // Append each chunk as it arrives
        $message->appendChunk($chunk);
    });

// Mark streaming as complete
$message->completeStreaming();
```

## Advanced Example: Multi-Provider Support

Here's a more complete example showing how to support multiple AI providers:

```php
<?php

namespace App\Services;

use EchoLabs\Prism\Facades\Prism;
use ElliottLawson\Converse\Models\Conversation;
use EchoLabs\Prism\Enums\Provider;

class AIConversationService
{
    private array $modelMap = [
        'openai' => 'gpt-4',
        'anthropic' => 'claude-3.5-sonnet',
        'gemini' => 'gemini-pro',
    ];

    public function generateResponse(
        Conversation $conversation, 
        string $provider = 'anthropic'
    ): string {
        // Start streaming message
        $assistantMessage = $conversation->startStreamingAssistant([
            'provider' => $provider,
            'model' => $this->modelMap[$provider],
        ]);

        try {
            // Generate response with Prism
            $response = Prism::text()
                ->using($this->getProvider($provider), $this->modelMap[$provider])
                ->withMessages($this->formatMessagesForProvider($conversation, $provider))
                ->withSystemPrompt($this->getSystemPrompt($conversation))
                ->withMaxTokens(2000)
                ->stream(function (string $chunk) use ($assistantMessage) {
                    $assistantMessage->appendChunk($chunk);
                });

            // Complete the message
            $assistantMessage->completeStreaming([
                'tokens' => $response->usage->totalTokens ?? null,
                'finish_reason' => $response->finishReason ?? null,
            ]);

            return $assistantMessage->content;
        } catch (\Exception $e) {
            $assistantMessage->failStreaming($e->getMessage());
            throw $e;
        }
    }

    private function formatMessagesForProvider(Conversation $conversation, string $provider): array
    {
        return $conversation->messages
            ->filter(fn($msg) => $msg->is_complete)
            ->map(function ($message) use ($provider) {
                // Handle provider-specific formatting
                if ($provider === 'anthropic' && $message->role->value === 'system') {
                    return null; // Anthropic handles system messages differently
                }

                return [
                    'role' => $message->role->value,
                    'content' => $message->content,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function getSystemPrompt(Conversation $conversation): ?string
    {
        $systemMessage = $conversation->messages()
            ->system()
            ->first();

        return $systemMessage?->content;
    }

    private function getProvider(string $provider): Provider
    {
        return match($provider) {
            'openai' => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            'gemini' => Provider::Gemini,
            default => Provider::Anthropic,
        };
    }
}
```

## Real-World Example: Code Assistant

Here's a complete example of building a code assistant using Prism and Converse:

```php
<?php

namespace App\Http\Controllers;

use App\Services\AIConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CodeAssistantController extends Controller
{
    public function __construct(
        private AIConversationService $aiService
    ) {}

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'sometimes|exists:ai_conversations,id',
            'language' => 'sometimes|string',
            'provider' => 'sometimes|in:openai,anthropic,gemini',
        ]);

        // Get or create conversation
        $conversation = $validated['conversation_id'] 
            ? Auth::user()->conversations()->findOrFail($validated['conversation_id'])
            : $this->createCodeAssistantConversation($validated['language'] ?? 'php');

        // Add user message
        $conversation->addUserMessage($validated['message']);

        // Generate AI response
        $response = $this->aiService->generateResponse(
            $conversation, 
            $validated['provider'] ?? 'anthropic'
        );

        return response()->json([
            'conversation_id' => $conversation->id,
            'response' => $response,
            'messages' => $conversation->messages->map(fn($msg) => [
                'id' => $msg->id,
                'role' => $msg->role->value,
                'content' => $msg->content,
                'created_at' => $msg->created_at,
            ]),
        ]);
    }

    private function createCodeAssistantConversation(string $language): Conversation
    {
        $conversation = Auth::user()->startConversation([
            'title' => "Code Assistant - {$language}",
            'metadata' => [
                'type' => 'code_assistant',
                'language' => $language,
            ],
        ]);

        // Add system prompt
        $conversation->addSystemMessage(
            "You are an expert {$language} developer assistant. " .
            "Provide clear, concise, and well-documented code examples. " .
            "Follow best practices and modern standards."
        );

        return $conversation;
    }
}
```

## Using Prism Tools with Converse

Prism's tool calling capability works seamlessly with Converse's tool message types:

```php
use EchoLabs\Prism\Tools\Tool;

// Define a tool
$weatherTool = Tool::create()
    ->withName('get_weather')
    ->withDescription('Get current weather for a location')
    ->withParameters([
        'location' => ['type' => 'string', 'required' => true],
    ])
    ->using(function (array $params) {
        // Your weather API logic here
        return "Current weather in {$params['location']}: Sunny, 72°F";
    });

// Use with conversation
$response = Prism::text()
    ->using('claude-3.5-sonnet')
    ->withMessages($conversation->messages->map(fn($msg) => [
        'role' => $msg->role->value,
        'content' => $msg->content
    ])->toArray())
    ->withTools([$weatherTool])
    ->generate();

// Handle tool calls
if ($response->hasFunctionCalls()) {
    foreach ($response->functionCalls as $call) {
        // Store tool call
        $conversation->addToolCallMessage(json_encode([
            'name' => $call->name,
            'arguments' => $call->arguments,
        ]));

        // Execute and store result
        $result = $call->execute();
        $conversation->addToolResultMessage($result);
    }
}
```

## Benefits of Using Prism with Converse

1. **Provider Flexibility**: Switch between AI providers without changing your conversation logic
2. **Streaming Support**: Native streaming integration between both packages
3. **Tool Integration**: Use Prism's tool system with Converse's tool message types
4. **Error Handling**: Robust error handling with automatic message status updates
5. **Token Tracking**: Track token usage in message metadata
6. **History Management**: Converse handles conversation history while Prism handles AI interaction

## Next Steps

- Explore [Prism's documentation](https://echolabs.dev/docs/prism) for more AI features
- Learn about [Converse Events](/guide/events) to add real-time updates
- Check out [Advanced Usage](/guide/advanced) for more patterns