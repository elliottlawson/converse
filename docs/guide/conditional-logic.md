# Conditional Logic

Converse provides two ways to conditionally add messages to conversations: **block conditionals** using `when/unless` and **inline conditionals** for simple cases.

## Two Approaches

### 1. Block Conditionals (when/unless)

Use `when()` and `unless()` for complex logic or multiple operations:

```php
$conversation = $conversation
    ->when($user->prefers_formal_tone, function ($conversation) {
        $conversation->addSystemMessage('Please maintain a professional tone.');
    })
    ->unless($user->is_anonymous, function ($conversation) use ($user) {
        $conversation->addSystemMessage("The user's name is {$user->name}.");
    })
    ->addUserMessage($userInput);
```

### 2. Inline Conditionals

Use inline methods for simple, single message conditions:

```php
$conversation = $conversation
    ->addMessageIf($user->needs_context, 'system', 'Include previous context in responses.')
    ->addMessageUnless($user->allows_links, 'system', 'Do not include external links.')
    ->addUserMessage($userInput);
```

## When to Use Each Approach

**Use block conditionals (`when/unless`) when:**
- You need to add multiple messages
- The logic requires additional operations
- You want to use if/else branches

**Use inline conditionals when:**
- Adding a single message based on a simple condition
- You want more readable, concise code
- The condition doesn't require complex logic

## Common Use Cases

Conditional logic is perfect for:
- Adapting to user preferences (language, tone, expertise level)
- Adjusting behavior based on user permissions or subscription tiers
- Building context from conversation history
- Enabling/disabling features based on environment or feature flags
- Personalizing responses based on user data

## Practical Example

Here's a practical example showing both approaches working together:

```php
// Using both approaches for different needs
$conversation = $conversation
    // Simple inline conditionals
    ->addMessageIf($user->language !== 'en', 'system', "Respond in {$user->language}")
    ->addMessageUnless($user->allows_links, 'system', 'Do not include external URLs')
    
    // Block conditional for complex logic
    ->when($user->subscription === 'premium', function ($conversation) use ($user) {
        $conversation
            ->addSystemMessage('User has premium features')
            ->addSystemMessage("Monthly limit: {$user->monthly_limit} messages");
    })
    
    ->addUserMessage($userInput);
```

## Available Methods

### Block Conditionals
- `when($condition, $callback)` - Execute callback if condition is true
- `when($condition, $callback, $default)` - Execute callback if true, otherwise execute default
- `unless($condition, $callback)` - Execute callback if condition is false

### Inline Conditionals
- `addMessageIf($condition, $role, $content, $metadata = [])` - Add message if condition is true
- `addMessageUnless($condition, $role, $content, $metadata = [])` - Add message if condition is false
- `createMessageIf(...)` - Same as above but returns the message instance
- `createMessageUnless(...)` - Same as above but returns the message instance

## Next Steps

- Learn about [View Support](/guide/view-support) for managing complex prompts
- Explore [Streaming Responses](/guide/streaming) for real-time conversations
- See practical [Examples](/examples/basic-chat) using conditional logic 