# Conditional Logic

The Conversation class uses Laravel's `Conditionable` trait, enabling powerful conditional logic in your fluent chains with `when()` and `unless()` methods. This allows you to build dynamic conversations that adapt to different contexts, user preferences, and application states.

## Basic Usage

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

## Conditional Message Addition Based on User Preferences

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

## Dynamic Context Building Based on Conversation State

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

## Conditional Tool/Function Availability

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

## Environment-Specific Behavior

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

## Complex Conditional Logic

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

## Chaining Multiple Conditions

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

## Inline Conditional Helpers

For simple conditional message additions, the conversation class provides inline conditional helper methods that offer a more concise syntax compared to `when()` and `unless()` closures. These methods are perfect when you need to conditionally add a single message without complex logic.

### Fluent API Methods

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

### Direct API Methods

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

### Comparison with when/unless

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

### With Metadata

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

### Practical Examples

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

## Next Steps

- Learn about [View Support](/guide/view-support) for managing complex prompts
- Explore [Streaming Responses](/guide/streaming) for real-time conversations
- See practical [Examples](/examples/basic-chat) using conditional logic 