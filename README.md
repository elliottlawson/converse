<p align="center">
  <img src="converse-logo.png" alt="Converse Logo" width="400">
</p>

<br>

<p align="center">
  <a href="https://github.com/elliottlawson/converse/actions/workflows/tests.yml"><img src="https://github.com/elliottlawson/converse/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://packagist.org/packages/elliottlawson/converse"><img src="https://poser.pugx.org/elliottlawson/converse/v/stable" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/elliottlawson/converse"><img src="https://poser.pugx.org/elliottlawson/converse/downloads" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/elliottlawson/converse"><img src="https://poser.pugx.org/elliottlawson/converse/license" alt="License"></a>
</p>

<br>

# Converse - AI Conversation Management for Laravel

**AI SDKs are great at sending messages, but terrible at having conversations.** 

Converse makes AI conversations flow as naturally as Eloquent makes database queries. Instead of manually managing message arrays and context for every API call, you just write `$conversation->addUserMessage('Hello')`. The entire conversation history, context management, and message formatting is handled automatically.

## 📚 Documentation

**[View the full documentation](https://converse-php.netlify.app)** - Comprehensive guides, API reference, and examples.

## The Difference

Without Converse, every API call means manually rebuilding context:

```php
// Manually track every message 😫
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $oldMessage1],
    ['role' => 'assistant', 'content' => $oldResponse1],
    ['role' => 'user', 'content' => $oldMessage2],
    ['role' => 'assistant', 'content' => $oldResponse2],
    ['role' => 'user', 'content' => $newMessage],
];

// Send to API
$response = $client->chat()->create(['messages' => $messages]);

// Now figure out how to save everything...
```

With Converse, conversations just flow:

```php
// Context is automatic ✨
$conversation->addUserMessage($newMessage);

// Your AI call gets the full context
$response = $aiClient->chat([
    'messages' => $conversation->messages->toArray()
]);

// Store the response
$conversation->addAssistantMessage($response->content);
```

That's it. **It's the difference between sending messages and actually having a conversation.**

## Features

- 💾 **Database-Backed Persistence** - Conversations survive page reloads and server restarts
- 🔌 **Provider Agnostic** - Works with OpenAI, Anthropic, Google, or any LLM
- 🌊 **Streaming Made Simple** - Automatic message chunking and progress tracking
- 🧠 **Automatic Context Management** - Stop rebuilding message arrays for every API call
- 📡 **Built-in Events** - Track usage, build analytics, react to AI interactions
- 🏗️ **Laravel Native** - Built with Eloquent, events, and broadcasting

## Installation

```bash
composer require elliottlawson/converse
```

Run the migrations:

```bash
php artisan migrate
```

## Quick Start

Add the trait to your User model:

```php
use ElliottLawson\Converse\Traits\HasAIConversations;

class User extends Model
{
    use HasAIConversations;
}
```

Start having conversations:

```php
// Build the conversation context
$conversation = $user->startConversation(['title' => 'My Chat'])
    ->addSystemMessage('You are a helpful assistant')
    ->addUserMessage('Hello! What is Laravel?');

// Make your API call to OpenAI, Anthropic, etc.
$response = $yourAiClient->chat([
    'messages' => $conversation->messages->toArray(),
    // ... other AI configuration
]);

// Store the AI's response
$conversation->addAssistantMessage($response->content);
```

## Requirements

- PHP 8.2+
- Laravel 11+

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.