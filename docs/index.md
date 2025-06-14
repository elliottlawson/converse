---
layout: home

hero:
  name: "Converse"
  text: "AI Conversation Management for Laravel"
  tagline: AI SDKs are great at sending messages, but terrible at having conversations. Converse makes AI conversations flow as naturally as Eloquent makes database queries.
  actions:
    - theme: brand
      text: Quick Start
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/elliottlawson/converse

features:
  - icon: 💾
    title: Database-Backed Persistence
    details: Conversations and messages are stored in your database, surviving page reloads and server restarts. Query your AI history with Eloquent.
    
  - icon: 🔌
    title: Provider Agnostic
    details: Works with OpenAI, Anthropic, Google, or any LLM. Switch providers without changing your code. Your data stays in your database.
    
  - icon: 🌊
    title: Streaming Made Simple
    details: Handle real-time AI responses elegantly. Automatic message chunking, progress tracking, and error recovery built-in.
    
  - icon: 🧠
    title: Automatic Context Management
    details: Stop manually rebuilding message arrays. Converse automatically formats conversation history for each API call and stores responses.
    
  - icon: 📡
    title: Built-in Events
    details: Comprehensive event system for every conversation action. Track usage, build analytics, and react to AI interactions in real-time.
    
  - icon: 🏗️
    title: Laravel Native
    details: Built with Eloquent models, Laravel events, and broadcasting. Feels like it belongs in your Laravel app because it does.

---

## Quick Start

Install the package via Composer:

```bash
composer require elliottlawson/converse
```

Add the trait to your User model:

```php
use ElliottLawson\Converse\Traits\HasAIConversations;

class User extends Model
{
    use HasAIConversations;
}
```

Start a conversation:

```php
$conversation = $user->startConversation(['title' => 'My Chat']);

$conversation
    ->addSystemMessage('You are a helpful assistant')
    ->addUserMessage('Hello!')
    ->addAssistantMessage('Hi! How can I help you today?');
```

[Learn more in the documentation →](/guide/getting-started)

## The Difference

Without Converse, every API call means manually rebuilding context:

```php
// Manually track every message
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
// Context is automatic
$conversation->addUserMessage($newMessage)->send();
```

That's it. The entire conversation history, context management, and message formatting is handled automatically. **It's the difference between sending messages and actually having a conversation.** 