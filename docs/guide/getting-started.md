# Setup

Get up and running with your first AI conversation in minutes.

## Prerequisites

Make sure you've completed the [installation](/guide/installation):
- Package installed via Composer
- Migrations run
- (Optional) Configuration published

## Your First Conversation

### 1. Add the Trait

Add the `HasAIConversations` trait to any model that should have conversations:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use ElliottLawson\Converse\Traits\HasAIConversations;

class User extends Model
{
    use HasAIConversations;
}
```

### 2. Start a Conversation

```php
$user = User::find(1);

// Start a new conversation
$conversation = $user->startConversation([
    'title' => 'My First AI Chat'
]);
```

### 3. Add Messages

```php
// Set the AI's behavior
$conversation->addSystemMessage('You are a helpful assistant.');

// Add a user question
$conversation->addUserMessage('What is Laravel?');

// Add the AI's response
$conversation->addAssistantMessage(
    'Laravel is a PHP web application framework with expressive, elegant syntax. 
    It provides tools for routing, sessions, caching, and more.'
);
```

## The Fluent API

Chain methods for a more natural flow:

```php
$conversation = $user->startConversation(['title' => 'Laravel Help'])
    ->addSystemMessage('You are a Laravel expert.')
    ->addUserMessage('How do I create a middleware?')
    ->addAssistantMessage('To create middleware: `php artisan make:middleware MyMiddleware`')
    ->addUserMessage('Where does it go?')
    ->addAssistantMessage('Middleware files are stored in `app/Http/Middleware/`');
```

## Continuing Conversations

Resume any conversation later:

```php
// Find an existing conversation
$conversation = $user->conversations()->find($conversationId);

// Or use the helper method
$conversation = $user->continueConversation($conversationId);

// Add more messages
$conversation->addUserMessage('Thanks! How do I register it?');
```

## Integrating with AI Providers

Here's a real example using OpenAI:

```php
use OpenAI\Laravel\Facades\OpenAI;

// Get user input
$userMessage = $request->input('message');

// Add to conversation
$conversation->addUserMessage($userMessage);

// Prepare context for AI
$messages = $conversation->messages->map(fn($msg) => [
    'role' => $msg->role->value,
    'content' => $msg->content
])->toArray();

// Get AI response
$response = OpenAI::chat()->create([
    'model' => 'gpt-4',
    'messages' => $messages,
]);

// Store the response
$conversation->addAssistantMessage($response->choices[0]->message->content);
```

## What's Next?

Now that you've created your first conversation, explore:

- [Message Types](/guide/messages) - Learn about all message types and when to use them
- [Conversations](/guide/conversations) - Deep dive into conversation management
- [Streaming](/guide/streaming) - Handle real-time streaming responses
- [Events](/guide/events) - React to conversation changes 